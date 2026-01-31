<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Services\BalanceCalculatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GroupController extends Controller
{
    public function __construct(
        private BalanceCalculatorService $balanceCalculator
    ) {}

    public function index(Request $request): JsonResponse
    {
        $groups = $request->user()
            ->groups()
            ->with(['creator:id,name', 'members:id,name,email,avatar'])
            ->withCount(['expenses', 'members'])
            ->get();

        return response()->json([
            'groups' => $groups,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['sometimes', 'in:home,trip,couple,other'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'member_ids' => ['sometimes', 'array'],
            'member_ids.*' => ['exists:users,id'],
        ]);

        $user = $request->user();

        $group = DB::transaction(function () use ($validated, $user) {
            $group = Group::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'type' => $validated['type'] ?? 'other',
                'currency' => $validated['currency'] ?? $user->currency_preference,
                'created_by' => $user->id,
            ]);

            GroupMember::create([
                'group_id' => $group->id,
                'user_id' => $user->id,
                'role' => 'admin',
                'joined_at' => now(),
            ]);

            if (!empty($validated['member_ids'])) {
                foreach ($validated['member_ids'] as $memberId) {
                    if ($memberId !== $user->id) {
                        GroupMember::create([
                            'group_id' => $group->id,
                            'user_id' => $memberId,
                            'role' => 'member',
                            'joined_at' => now(),
                        ]);
                    }
                }
            }

            ActivityLog::create([
                'user_id' => $user->id,
                'group_id' => $group->id,
                'action' => 'group_created',
                'description' => "Created group '{$group->name}'",
                'subject_type' => Group::class,
                'subject_id' => $group->id,
            ]);

            return $group;
        });

        return response()->json([
            'message' => 'Group created successfully',
            'group' => $group->load(['creator:id,name', 'members:id,name,email,avatar']),
        ], 201);
    }

    public function show(Request $request, Group $group): JsonResponse
    {
        $this->authorizeGroupAccess($request->user(), $group);

        $group->load(['creator:id,name', 'members:id,name,email,avatar', 'expenses' => function ($query) {
            $query->latest()->limit(10);
        }]);

        return response()->json([
            'group' => $group,
        ]);
    }

    public function update(Request $request, Group $group): JsonResponse
    {
        $this->authorizeGroupAdmin($request->user(), $group);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['sometimes', 'in:home,trip,couple,other'],
            'currency' => ['sometimes', 'string', 'size:3'],
        ]);

        $group->update($validated);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'group_id' => $group->id,
            'action' => 'group_updated',
            'description' => "Updated group '{$group->name}'",
            'subject_type' => Group::class,
            'subject_id' => $group->id,
        ]);

        return response()->json([
            'message' => 'Group updated successfully',
            'group' => $group->fresh(['creator:id,name', 'members:id,name,email,avatar']),
        ]);
    }

    public function destroy(Request $request, Group $group): JsonResponse
    {
        $this->authorizeGroupAdmin($request->user(), $group);

        $group->delete();

        return response()->json([
            'message' => 'Group deleted successfully',
        ]);
    }

    public function addMember(Request $request, Group $group): JsonResponse
    {
        $this->authorizeGroupAdmin($request->user(), $group);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $existingMember = GroupMember::where('group_id', $group->id)
            ->where('user_id', $validated['user_id'])
            ->exists();

        if ($existingMember) {
            return response()->json([
                'message' => 'User is already a member of this group',
            ], 422);
        }

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $validated['user_id'],
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $newMember = User::find($validated['user_id']);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'group_id' => $group->id,
            'action' => 'member_added',
            'description' => "Added {$newMember->name} to the group",
            'subject_type' => GroupMember::class,
            'subject_id' => $validated['user_id'],
        ]);

        return response()->json([
            'message' => 'Member added successfully',
            'member' => $newMember->only(['id', 'name', 'email', 'avatar']),
        ], 201);
    }

    public function removeMember(Request $request, Group $group, User $user): JsonResponse
    {
        $this->authorizeGroupAdmin($request->user(), $group);

        if ($group->created_by === $user->id) {
            return response()->json([
                'message' => 'Cannot remove the group creator',
            ], 422);
        }

        GroupMember::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->delete();

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'group_id' => $group->id,
            'action' => 'member_removed',
            'description' => "Removed {$user->name} from the group",
            'subject_type' => GroupMember::class,
            'subject_id' => $user->id,
        ]);

        return response()->json([
            'message' => 'Member removed successfully',
        ]);
    }

    public function updateMemberRole(Request $request, Group $group, User $user): JsonResponse
    {
        $this->authorizeGroupAdmin($request->user(), $group);

        $validated = $request->validate([
            'role' => ['required', 'in:admin,member'],
        ]);

        GroupMember::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->update(['role' => $validated['role']]);

        return response()->json([
            'message' => 'Member role updated successfully',
        ]);
    }

    public function balances(Request $request, Group $group): JsonResponse
    {
        $this->authorizeGroupAccess($request->user(), $group);

        $balances = $this->balanceCalculator->calculateGroupBalances($group);

        return response()->json([
            'balances' => $balances,
        ]);
    }

    public function expenses(Request $request, Group $group): JsonResponse
    {
        $this->authorizeGroupAccess($request->user(), $group);

        $expenses = $group->expenses()
            ->with(['payer:id,name', 'category', 'splits.user:id,name'])
            ->latest()
            ->paginate(20);

        return response()->json($expenses);
    }

    public function settlements(Request $request, Group $group): JsonResponse
    {
        $this->authorizeGroupAccess($request->user(), $group);

        $settlements = $group->settlements()
            ->with(['payer:id,name', 'payee:id,name'])
            ->latest()
            ->paginate(20);

        return response()->json($settlements);
    }

    private function authorizeGroupAccess(User $user, Group $group): void
    {
        if (!$group->members()->where('user_id', $user->id)->exists()) {
            abort(403, 'You are not a member of this group');
        }
    }

    private function authorizeGroupAdmin(User $user, Group $group): void
    {
        $member = GroupMember::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$member || $member->role !== 'admin') {
            abort(403, 'You do not have admin privileges for this group');
        }
    }
}
