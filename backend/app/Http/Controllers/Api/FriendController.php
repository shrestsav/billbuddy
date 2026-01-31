<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Friend;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FriendController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $friends = $user->friends()->get(['users.id', 'name', 'email', 'avatar']);

        return response()->json([
            'friends' => $friends,
        ]);
    }

    public function pending(Request $request): JsonResponse
    {
        $user = $request->user();

        $received = Friend::with('user:id,name,email,avatar')
            ->where('friend_id', $user->id)
            ->where('status', 'pending')
            ->get();

        $sent = Friend::with('friend:id,name,email,avatar')
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->get();

        return response()->json([
            'received' => $received,
            'sent' => $sent,
        ]);
    }

    public function invite(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $user = $request->user();
        $friend = User::where('email', $request->email)->first();

        if ($friend->id === $user->id) {
            return response()->json([
                'message' => 'You cannot add yourself as a friend',
            ], 422);
        }

        $existing = Friend::where(function ($query) use ($user, $friend) {
            $query->where('user_id', $user->id)->where('friend_id', $friend->id);
        })->orWhere(function ($query) use ($user, $friend) {
            $query->where('user_id', $friend->id)->where('friend_id', $user->id);
        })->first();

        if ($existing) {
            return response()->json([
                'message' => $existing->status === 'accepted'
                    ? 'You are already friends'
                    : 'Friend request already exists',
            ], 422);
        }

        $friendRequest = Friend::create([
            'user_id' => $user->id,
            'friend_id' => $friend->id,
            'status' => 'pending',
        ]);

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'friend_request_sent',
            'description' => "Sent friend request to {$friend->name}",
            'subject_type' => Friend::class,
            'subject_id' => $friendRequest->id,
        ]);

        return response()->json([
            'message' => 'Friend request sent',
            'friend_request' => $friendRequest->load('friend:id,name,email,avatar'),
        ], 201);
    }

    public function accept(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $friendRequest = Friend::where('id', $id)
            ->where('friend_id', $user->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $friendRequest->update(['status' => 'accepted']);

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'friend_request_accepted',
            'description' => "Became friends with {$friendRequest->user->name}",
            'subject_type' => Friend::class,
            'subject_id' => $friendRequest->id,
        ]);

        return response()->json([
            'message' => 'Friend request accepted',
            'friend' => $friendRequest->user()->first(['id', 'name', 'email', 'avatar']),
        ]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $friendRequest = Friend::where('id', $id)
            ->where('friend_id', $user->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $friendRequest->delete();

        return response()->json([
            'message' => 'Friend request rejected',
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $friendship = Friend::where(function ($query) use ($user, $id) {
            $query->where('user_id', $user->id)->where('friend_id', $id);
        })->orWhere(function ($query) use ($user, $id) {
            $query->where('user_id', $id)->where('friend_id', $user->id);
        })->firstOrFail();

        $friendship->delete();

        return response()->json([
            'message' => 'Friend removed',
        ]);
    }
}
