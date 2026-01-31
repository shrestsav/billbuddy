<?php

namespace App\Services;

use App\Models\Group;
use App\Models\User;

class DebtSimplifierService
{
    public function __construct(
        private BalanceCalculatorService $balanceCalculator
    ) {}

    public function simplifyGroupDebts(Group $group): array
    {
        $groupBalances = $this->balanceCalculator->calculateGroupBalances($group);

        $netBalances = [];

        foreach ($groupBalances as $memberBalance) {
            $userId = $memberBalance['user']['id'];
            $net = $memberBalance['total_owed'] - $memberBalance['total_owing'];
            $netBalances[$userId] = [
                'user' => $memberBalance['user'],
                'balance' => $net,
            ];
        }

        return $this->simplifyDebts($netBalances);
    }

    public function simplifyUserDebts(User $user): array
    {
        $balanceData = $this->balanceCalculator->calculateUserBalances($user);
        $balances = $balanceData['balances'];

        $netBalances = [];

        $netBalances[$user->id] = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
            'balance' => $balanceData['net_balance'],
        ];

        foreach ($balances as $balance) {
            $otherUserId = $balance['user']['id'];
            $amount = $balance['direction'] === 'you_owe' ? -$balance['amount'] : $balance['amount'];
            $netBalances[$otherUserId] = [
                'user' => $balance['user'],
                'balance' => -$amount,
            ];
        }

        return $this->simplifyDebts($netBalances);
    }

    private function simplifyDebts(array $netBalances): array
    {
        $creditors = [];
        $debtors = [];

        foreach ($netBalances as $userId => $data) {
            $balance = $data['balance'];
            if ($balance > 0.01) {
                $creditors[] = [
                    'user' => $data['user'],
                    'amount' => $balance,
                ];
            } elseif ($balance < -0.01) {
                $debtors[] = [
                    'user' => $data['user'],
                    'amount' => abs($balance),
                ];
            }
        }

        usort($creditors, fn($a, $b) => $b['amount'] <=> $a['amount']);
        usort($debtors, fn($a, $b) => $b['amount'] <=> $a['amount']);

        $simplifiedDebts = [];

        while (!empty($creditors) && !empty($debtors)) {
            $creditor = &$creditors[0];
            $debtor = &$debtors[0];

            $settlementAmount = min($creditor['amount'], $debtor['amount']);

            if ($settlementAmount > 0.01) {
                $simplifiedDebts[] = [
                    'from' => $debtor['user'],
                    'to' => $creditor['user'],
                    'amount' => round($settlementAmount, 2),
                ];
            }

            $creditor['amount'] -= $settlementAmount;
            $debtor['amount'] -= $settlementAmount;

            if ($creditor['amount'] < 0.01) {
                array_shift($creditors);
            }

            if ($debtor['amount'] < 0.01) {
                array_shift($debtors);
            }
        }

        return $simplifiedDebts;
    }
}
