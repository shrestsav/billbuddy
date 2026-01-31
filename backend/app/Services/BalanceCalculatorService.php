<?php

namespace App\Services;

use App\Models\ExpenseSplit;
use App\Models\Group;
use App\Models\Settlement;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BalanceCalculatorService
{
    public function calculateUserBalances(User $user, ?int $groupId = null): array
    {
        $balances = [];

        $owedToUser = $this->getAmountsOwedToUser($user, $groupId);
        $userOwes = $this->getAmountsUserOwes($user, $groupId);
        $settlementsReceived = $this->getSettlementsReceived($user, $groupId);
        $settlementsPaid = $this->getSettlementsPaid($user, $groupId);

        $allUserIds = collect()
            ->merge($owedToUser->keys())
            ->merge($userOwes->keys())
            ->merge($settlementsReceived->keys())
            ->merge($settlementsPaid->keys())
            ->unique();

        foreach ($allUserIds as $otherId) {
            $owed = $owedToUser->get($otherId, 0);
            $owes = $userOwes->get($otherId, 0);
            $received = $settlementsReceived->get($otherId, 0);
            $paid = $settlementsPaid->get($otherId, 0);

            $netBalance = ($owed - $owes) + ($paid - $received);

            if (abs($netBalance) > 0.01) {
                $otherUser = User::find($otherId);
                $balances[] = [
                    'user' => [
                        'id' => $otherUser->id,
                        'name' => $otherUser->name,
                        'email' => $otherUser->email,
                        'avatar' => $otherUser->avatar,
                    ],
                    'amount' => abs($netBalance),
                    'direction' => $netBalance > 0 ? 'owed_to_you' : 'you_owe',
                ];
            }
        }

        $totalOwed = collect($balances)->where('direction', 'owed_to_you')->sum('amount');
        $totalOwing = collect($balances)->where('direction', 'you_owe')->sum('amount');

        return [
            'balances' => $balances,
            'total_owed' => round($totalOwed, 2),
            'total_owing' => round($totalOwing, 2),
            'net_balance' => round($totalOwed - $totalOwing, 2),
        ];
    }

    public function calculateGroupBalances(Group $group): array
    {
        $members = $group->members;
        $balances = [];

        foreach ($members as $member) {
            $memberBalances = [];

            foreach ($members as $otherMember) {
                if ($member->id === $otherMember->id) {
                    continue;
                }

                $owed = $this->getAmountOwedBetweenUsers($member, $otherMember, $group->id);
                $owes = $this->getAmountOwedBetweenUsers($otherMember, $member, $group->id);

                $settlementsReceived = Settlement::where('group_id', $group->id)
                    ->where('payer_id', $otherMember->id)
                    ->where('payee_id', $member->id)
                    ->sum('amount');

                $settlementsPaid = Settlement::where('group_id', $group->id)
                    ->where('payer_id', $member->id)
                    ->where('payee_id', $otherMember->id)
                    ->sum('amount');

                $netBalance = ($owed - $owes) + ($settlementsPaid - $settlementsReceived);

                if (abs($netBalance) > 0.01) {
                    $memberBalances[] = [
                        'user' => [
                            'id' => $otherMember->id,
                            'name' => $otherMember->name,
                        ],
                        'amount' => abs($netBalance),
                        'direction' => $netBalance > 0 ? 'owed_to_you' : 'you_owe',
                    ];
                }
            }

            $balances[] = [
                'user' => [
                    'id' => $member->id,
                    'name' => $member->name,
                ],
                'balances' => $memberBalances,
                'total_owed' => collect($memberBalances)->where('direction', 'owed_to_you')->sum('amount'),
                'total_owing' => collect($memberBalances)->where('direction', 'you_owe')->sum('amount'),
            ];
        }

        return $balances;
    }

    private function getAmountsOwedToUser(User $user, ?int $groupId): Collection
    {
        $query = ExpenseSplit::query()
            ->join('expenses', 'expense_splits.expense_id', '=', 'expenses.id')
            ->where('expenses.paid_by', $user->id)
            ->where('expense_splits.user_id', '!=', $user->id)
            ->select('expense_splits.user_id', DB::raw('SUM(expense_splits.amount) as total'))
            ->groupBy('expense_splits.user_id');

        if ($groupId) {
            $query->where('expenses.group_id', $groupId);
        }

        return $query->pluck('total', 'user_id');
    }

    private function getAmountsUserOwes(User $user, ?int $groupId): Collection
    {
        $query = ExpenseSplit::query()
            ->join('expenses', 'expense_splits.expense_id', '=', 'expenses.id')
            ->where('expense_splits.user_id', $user->id)
            ->where('expenses.paid_by', '!=', $user->id)
            ->select('expenses.paid_by', DB::raw('SUM(expense_splits.amount) as total'))
            ->groupBy('expenses.paid_by');

        if ($groupId) {
            $query->where('expenses.group_id', $groupId);
        }

        return $query->pluck('total', 'paid_by');
    }

    private function getSettlementsReceived(User $user, ?int $groupId): Collection
    {
        $query = Settlement::where('payee_id', $user->id)
            ->select('payer_id', DB::raw('SUM(amount) as total'))
            ->groupBy('payer_id');

        if ($groupId) {
            $query->where('group_id', $groupId);
        }

        return $query->pluck('total', 'payer_id');
    }

    private function getSettlementsPaid(User $user, ?int $groupId): Collection
    {
        $query = Settlement::where('payer_id', $user->id)
            ->select('payee_id', DB::raw('SUM(amount) as total'))
            ->groupBy('payee_id');

        if ($groupId) {
            $query->where('group_id', $groupId);
        }

        return $query->pluck('total', 'payee_id');
    }

    private function getAmountOwedBetweenUsers(User $payer, User $debtor, int $groupId): float
    {
        return ExpenseSplit::query()
            ->join('expenses', 'expense_splits.expense_id', '=', 'expenses.id')
            ->where('expenses.group_id', $groupId)
            ->where('expenses.paid_by', $payer->id)
            ->where('expense_splits.user_id', $debtor->id)
            ->sum('expense_splits.amount');
    }
}
