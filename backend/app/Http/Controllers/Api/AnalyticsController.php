<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseSplit;
use App\Models\Group;
use App\Services\BalanceCalculatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function __construct(
        private BalanceCalculatorService $balanceCalculator
    ) {}

    public function spendingByCategory(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'from_date' => ['sometimes', 'date'],
            'to_date' => ['sometimes', 'date'],
            'group_id' => ['sometimes', 'exists:groups,id'],
        ]);

        $query = ExpenseSplit::query()
            ->join('expenses', 'expense_splits.expense_id', '=', 'expenses.id')
            ->leftJoin('categories', 'expenses.category_id', '=', 'categories.id')
            ->where('expense_splits.user_id', $user->id)
            ->select(
                DB::raw('COALESCE(categories.name, "Uncategorized") as category'),
                DB::raw('COALESCE(categories.icon, "receipt") as icon'),
                DB::raw('COALESCE(categories.color, "#6B7280") as color'),
                DB::raw('SUM(expense_splits.amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('categories.id', 'categories.name', 'categories.icon', 'categories.color');

        if ($request->has('from_date')) {
            $query->whereDate('expenses.date', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('expenses.date', '<=', $request->to_date);
        }

        if ($request->has('group_id')) {
            $query->where('expenses.group_id', $request->group_id);
        }

        $spending = $query->orderByDesc('total')->get();

        $total = $spending->sum('total');

        $spending = $spending->map(function ($item) use ($total) {
            $item->percentage = $total > 0 ? round(($item->total / $total) * 100, 1) : 0;
            return $item;
        });

        return response()->json([
            'spending_by_category' => $spending,
            'total' => $total,
        ]);
    }

    public function spendingOverTime(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'period' => ['sometimes', 'in:daily,weekly,monthly'],
            'from_date' => ['sometimes', 'date'],
            'to_date' => ['sometimes', 'date'],
            'group_id' => ['sometimes', 'exists:groups,id'],
        ]);

        $period = $request->input('period', 'monthly');
        $fromDate = $request->input('from_date', now()->subMonths(6)->startOfMonth());
        $toDate = $request->input('to_date', now());

        $dateFormat = match ($period) {
            'daily' => '%Y-%m-%d',
            'weekly' => '%Y-%u',
            'monthly' => '%Y-%m',
        };

        $query = ExpenseSplit::query()
            ->join('expenses', 'expense_splits.expense_id', '=', 'expenses.id')
            ->where('expense_splits.user_id', $user->id)
            ->whereDate('expenses.date', '>=', $fromDate)
            ->whereDate('expenses.date', '<=', $toDate)
            ->select(
                DB::raw("DATE_FORMAT(expenses.date, '{$dateFormat}') as period"),
                DB::raw('SUM(expense_splits.amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('period')
            ->orderBy('period');

        if ($request->has('group_id')) {
            $query->where('expenses.group_id', $request->group_id);
        }

        $spending = $query->get();

        return response()->json([
            'spending_over_time' => $spending,
            'period' => $period,
        ]);
    }

    public function groupSummary(Request $request, Group $group): JsonResponse
    {
        $user = $request->user();

        if (!$group->members()->where('user_id', $user->id)->exists()) {
            abort(403, 'You are not a member of this group');
        }

        $totalExpenses = $group->expenses()->sum('amount');
        $expenseCount = $group->expenses()->count();
        $memberCount = $group->members()->count();

        $topSpenders = $group->expenses()
            ->select('paid_by', DB::raw('SUM(amount) as total'))
            ->with('payer:id,name')
            ->groupBy('paid_by')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'user' => $item->payer,
                    'total' => $item->total,
                ];
            });

        $categoryBreakdown = $group->expenses()
            ->leftJoin('categories', 'expenses.category_id', '=', 'categories.id')
            ->select(
                DB::raw('COALESCE(categories.name, "Uncategorized") as category'),
                DB::raw('SUM(expenses.amount) as total')
            )
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total')
            ->get();

        $balances = $this->balanceCalculator->calculateGroupBalances($group);

        return response()->json([
            'group' => $group->only(['id', 'name', 'currency']),
            'total_expenses' => $totalExpenses,
            'expense_count' => $expenseCount,
            'member_count' => $memberCount,
            'average_expense' => $expenseCount > 0 ? round($totalExpenses / $expenseCount, 2) : 0,
            'top_spenders' => $topSpenders,
            'category_breakdown' => $categoryBreakdown,
            'balances' => $balances,
        ]);
    }

    public function monthlySummary(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'month' => ['sometimes', 'date_format:Y-m'],
        ]);

        $month = $request->input('month', now()->format('Y-m'));
        $startDate = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $totalOwed = ExpenseSplit::query()
            ->join('expenses', 'expense_splits.expense_id', '=', 'expenses.id')
            ->where('expense_splits.user_id', $user->id)
            ->where('expenses.paid_by', '!=', $user->id)
            ->whereDate('expenses.date', '>=', $startDate)
            ->whereDate('expenses.date', '<=', $endDate)
            ->sum('expense_splits.amount');

        $totalPaid = Expense::where('paid_by', $user->id)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->sum('amount');

        $yourShare = ExpenseSplit::query()
            ->join('expenses', 'expense_splits.expense_id', '=', 'expenses.id')
            ->where('expense_splits.user_id', $user->id)
            ->whereDate('expenses.date', '>=', $startDate)
            ->whereDate('expenses.date', '<=', $endDate)
            ->sum('expense_splits.amount');

        $expenseCount = Expense::whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->where(function ($q) use ($user) {
                $q->where('paid_by', $user->id)
                    ->orWhereHas('splits', function ($sq) use ($user) {
                        $sq->where('user_id', $user->id);
                    });
            })
            ->count();

        return response()->json([
            'month' => $month,
            'total_paid' => $totalPaid,
            'your_share' => $yourShare,
            'total_owed' => $totalOwed,
            'expense_count' => $expenseCount,
        ]);
    }
}
