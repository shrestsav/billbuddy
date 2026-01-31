<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Group;
use App\Models\Settlement;
use App\Services\BalanceCalculatorService;
use App\Services\DebtSimplifierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettlementController extends Controller
{
    public function __construct(
        private BalanceCalculatorService $balanceCalculator,
        private DebtSimplifierService $debtSimplifier
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Settlement::with(['payer:id,name', 'payee:id,name', 'group:id,name'])
            ->where(function ($q) use ($user) {
                $q->where('payer_id', $user->id)
                    ->orWhere('payee_id', $user->id);
            });

        if ($request->has('group_id')) {
            $query->where('group_id', $request->group_id);
        }

        $settlements = $query->latest()->paginate(20);

        return response()->json($settlements);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payee_id' => ['required', 'exists:users,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'group_id' => ['nullable', 'exists:groups,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'date' => ['sometimes', 'date'],
        ]);

        $user = $request->user();

        if ($validated['payee_id'] === $user->id) {
            return response()->json([
                'message' => 'You cannot settle with yourself',
            ], 422);
        }

        if ($validated['group_id']) {
            $group = Group::findOrFail($validated['group_id']);
            if (!$group->members()->where('user_id', $user->id)->exists()) {
                abort(403, 'You are not a member of this group');
            }
        }

        $settlement = Settlement::create([
            'payer_id' => $user->id,
            'payee_id' => $validated['payee_id'],
            'amount' => $validated['amount'],
            'currency' => $validated['currency'] ?? $user->currency_preference,
            'group_id' => $validated['group_id'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'date' => $validated['date'] ?? now(),
        ]);

        ActivityLog::create([
            'user_id' => $user->id,
            'group_id' => $validated['group_id'] ?? null,
            'action' => 'settlement_created',
            'description' => "Settled {$settlement->currency} {$settlement->amount} with {$settlement->payee->name}",
            'subject_type' => Settlement::class,
            'subject_id' => $settlement->id,
        ]);

        return response()->json([
            'message' => 'Settlement recorded successfully',
            'settlement' => $settlement->load(['payer:id,name', 'payee:id,name', 'group:id,name']),
        ], 201);
    }

    public function show(Settlement $settlement): JsonResponse
    {
        $user = request()->user();

        if ($settlement->payer_id !== $user->id && $settlement->payee_id !== $user->id) {
            abort(403, 'You do not have access to this settlement');
        }

        return response()->json([
            'settlement' => $settlement->load(['payer:id,name,email', 'payee:id,name,email', 'group:id,name']),
        ]);
    }

    public function balances(Request $request): JsonResponse
    {
        $user = $request->user();
        $groupId = $request->query('group_id');

        $balances = $this->balanceCalculator->calculateUserBalances($user, $groupId);

        return response()->json([
            'balances' => $balances,
            'total_owed' => $balances['total_owed'],
            'total_owing' => $balances['total_owing'],
            'net_balance' => $balances['net_balance'],
        ]);
    }

    public function simplifiedBalances(Request $request): JsonResponse
    {
        $user = $request->user();
        $groupId = $request->query('group_id');

        if ($groupId) {
            $group = Group::findOrFail($groupId);
            if (!$group->members()->where('user_id', $user->id)->exists()) {
                abort(403, 'You are not a member of this group');
            }
            $simplifiedDebts = $this->debtSimplifier->simplifyGroupDebts($group);
        } else {
            $simplifiedDebts = $this->debtSimplifier->simplifyUserDebts($user);
        }

        return response()->json([
            'simplified_debts' => $simplifiedDebts,
        ]);
    }
}
