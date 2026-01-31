<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Expense;
use App\Models\ExpenseSplit;
use App\Models\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Expense::with(['payer:id,name', 'group:id,name', 'category', 'splits.user:id,name'])
            ->where(function ($q) use ($user) {
                $q->where('paid_by', $user->id)
                    ->orWhereHas('splits', function ($sq) use ($user) {
                        $sq->where('user_id', $user->id);
                    });
            });

        if ($request->has('group_id')) {
            $query->where('group_id', $request->group_id);
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('from_date')) {
            $query->whereDate('date', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        $expenses = $query->latest('date')->paginate(20);

        return response()->json($expenses);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'date' => ['sometimes', 'date'],
            'group_id' => ['nullable', 'exists:groups,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'split_type' => ['required', 'in:equal,percentage,shares,exact'],
            'splits' => ['required', 'array', 'min:1'],
            'splits.*.user_id' => ['required', 'exists:users,id'],
            'splits.*.value' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_recurring' => ['sometimes', 'boolean'],
            'recurring_frequency' => ['required_if:is_recurring,true', 'in:daily,weekly,monthly,yearly'],
        ]);

        $user = $request->user();

        if ($validated['group_id']) {
            $group = Group::findOrFail($validated['group_id']);
            if (!$group->members()->where('user_id', $user->id)->exists()) {
                abort(403, 'You are not a member of this group');
            }
        }

        $splits = $this->calculateSplits(
            $validated['amount'],
            $validated['split_type'],
            $validated['splits']
        );

        $expense = DB::transaction(function () use ($validated, $user, $splits) {
            $expense = Expense::create([
                'description' => $validated['description'],
                'amount' => $validated['amount'],
                'currency' => $validated['currency'] ?? $user->currency_preference,
                'date' => $validated['date'] ?? now(),
                'paid_by' => $user->id,
                'group_id' => $validated['group_id'] ?? null,
                'category_id' => $validated['category_id'] ?? null,
                'split_type' => $validated['split_type'],
                'notes' => $validated['notes'] ?? null,
                'is_recurring' => $validated['is_recurring'] ?? false,
                'recurring_frequency' => $validated['recurring_frequency'] ?? null,
            ]);

            foreach ($splits as $split) {
                ExpenseSplit::create([
                    'expense_id' => $expense->id,
                    'user_id' => $split['user_id'],
                    'amount' => $split['amount'],
                    'percentage' => $split['percentage'] ?? null,
                    'shares' => $split['shares'] ?? null,
                ]);
            }

            ActivityLog::create([
                'user_id' => $user->id,
                'group_id' => $validated['group_id'] ?? null,
                'action' => 'expense_created',
                'description' => "Added expense '{$expense->description}' for {$expense->currency} {$expense->amount}",
                'subject_type' => Expense::class,
                'subject_id' => $expense->id,
            ]);

            return $expense;
        });

        return response()->json([
            'message' => 'Expense created successfully',
            'expense' => $expense->load(['payer:id,name', 'category', 'splits.user:id,name']),
        ], 201);
    }

    public function show(Expense $expense): JsonResponse
    {
        $this->authorizeExpenseAccess(request()->user(), $expense);

        return response()->json([
            'expense' => $expense->load(['payer:id,name', 'group:id,name', 'category', 'splits.user:id,name,email']),
        ]);
    }

    public function update(Request $request, Expense $expense): JsonResponse
    {
        $this->authorizeExpenseModification($request->user(), $expense);

        $validated = $request->validate([
            'description' => ['sometimes', 'string', 'max:255'],
            'amount' => ['sometimes', 'numeric', 'min:0.01'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'date' => ['sometimes', 'date'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'split_type' => ['sometimes', 'in:equal,percentage,shares,exact'],
            'splits' => ['required_with:split_type', 'array', 'min:1'],
            'splits.*.user_id' => ['required_with:splits', 'exists:users,id'],
            'splits.*.value' => ['required_with:splits', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($validated, $expense, $request) {
            $amount = $validated['amount'] ?? $expense->amount;
            $splitType = $validated['split_type'] ?? $expense->split_type;

            $expense->update([
                'description' => $validated['description'] ?? $expense->description,
                'amount' => $amount,
                'currency' => $validated['currency'] ?? $expense->currency,
                'date' => $validated['date'] ?? $expense->date,
                'category_id' => array_key_exists('category_id', $validated) ? $validated['category_id'] : $expense->category_id,
                'split_type' => $splitType,
                'notes' => array_key_exists('notes', $validated) ? $validated['notes'] : $expense->notes,
            ]);

            if (isset($validated['splits'])) {
                $expense->splits()->delete();

                $splits = $this->calculateSplits($amount, $splitType, $validated['splits']);

                foreach ($splits as $split) {
                    ExpenseSplit::create([
                        'expense_id' => $expense->id,
                        'user_id' => $split['user_id'],
                        'amount' => $split['amount'],
                        'percentage' => $split['percentage'] ?? null,
                        'shares' => $split['shares'] ?? null,
                    ]);
                }
            }

            ActivityLog::create([
                'user_id' => $request->user()->id,
                'group_id' => $expense->group_id,
                'action' => 'expense_updated',
                'description' => "Updated expense '{$expense->description}'",
                'subject_type' => Expense::class,
                'subject_id' => $expense->id,
            ]);
        });

        return response()->json([
            'message' => 'Expense updated successfully',
            'expense' => $expense->fresh(['payer:id,name', 'category', 'splits.user:id,name']),
        ]);
    }

    public function destroy(Request $request, Expense $expense): JsonResponse
    {
        $this->authorizeExpenseModification($request->user(), $expense);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'group_id' => $expense->group_id,
            'action' => 'expense_deleted',
            'description' => "Deleted expense '{$expense->description}'",
            'subject_type' => Expense::class,
            'subject_id' => $expense->id,
        ]);

        $expense->delete();

        return response()->json([
            'message' => 'Expense deleted successfully',
        ]);
    }

    public function uploadReceipt(Request $request, Expense $expense): JsonResponse
    {
        $this->authorizeExpenseModification($request->user(), $expense);

        $request->validate([
            'receipt' => ['required', 'image', 'max:5120'],
        ]);

        if ($expense->receipt_path) {
            Storage::disk('public')->delete($expense->receipt_path);
        }

        $path = $request->file('receipt')->store('receipts', 'public');
        $expense->update(['receipt_path' => $path]);

        return response()->json([
            'message' => 'Receipt uploaded successfully',
            'receipt_url' => Storage::disk('public')->url($path),
        ]);
    }

    private function calculateSplits(float $amount, string $splitType, array $splits): array
    {
        $result = [];

        switch ($splitType) {
            case 'equal':
                $count = count($splits);
                $equalAmount = round($amount / $count, 2);
                $remainder = $amount - ($equalAmount * $count);

                foreach ($splits as $index => $split) {
                    $splitAmount = $equalAmount;
                    if ($index === 0) {
                        $splitAmount += $remainder;
                    }
                    $result[] = [
                        'user_id' => $split['user_id'],
                        'amount' => $splitAmount,
                    ];
                }
                break;

            case 'percentage':
                $totalPercentage = array_sum(array_column($splits, 'value'));
                if (abs($totalPercentage - 100) > 0.01) {
                    abort(422, 'Percentages must sum to 100');
                }

                foreach ($splits as $split) {
                    $result[] = [
                        'user_id' => $split['user_id'],
                        'amount' => round($amount * ($split['value'] / 100), 2),
                        'percentage' => $split['value'],
                    ];
                }
                break;

            case 'shares':
                $totalShares = array_sum(array_column($splits, 'value'));
                if ($totalShares <= 0) {
                    abort(422, 'Total shares must be greater than 0');
                }

                foreach ($splits as $split) {
                    $result[] = [
                        'user_id' => $split['user_id'],
                        'amount' => round($amount * ($split['value'] / $totalShares), 2),
                        'shares' => $split['value'],
                    ];
                }
                break;

            case 'exact':
                $totalExact = array_sum(array_column($splits, 'value'));
                if (abs($totalExact - $amount) > 0.01) {
                    abort(422, 'Exact amounts must sum to total amount');
                }

                foreach ($splits as $split) {
                    $result[] = [
                        'user_id' => $split['user_id'],
                        'amount' => $split['value'],
                    ];
                }
                break;
        }

        return $result;
    }

    private function authorizeExpenseAccess($user, Expense $expense): void
    {
        $isParticipant = $expense->paid_by === $user->id ||
            $expense->splits()->where('user_id', $user->id)->exists();

        if (!$isParticipant) {
            abort(403, 'You do not have access to this expense');
        }
    }

    private function authorizeExpenseModification($user, Expense $expense): void
    {
        if ($expense->paid_by !== $user->id) {
            abort(403, 'Only the payer can modify this expense');
        }
    }
}
