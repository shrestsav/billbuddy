<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'group_id' => ['sometimes', 'exists:groups,id'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $groupIds = $user->groups()->pluck('groups.id');

        $query = ActivityLog::with(['user:id,name,avatar', 'group:id,name'])
            ->where(function ($q) use ($user, $groupIds) {
                $q->where('user_id', $user->id)
                    ->orWhereIn('group_id', $groupIds);
            });

        if ($request->has('group_id')) {
            if (!$groupIds->contains($request->group_id)) {
                abort(403, 'You are not a member of this group');
            }
            $query->where('group_id', $request->group_id);
        }

        $limit = $request->input('limit', 50);
        $activities = $query->latest()->paginate($limit);

        return response()->json($activities);
    }
}
