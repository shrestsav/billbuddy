<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'min:3'],
        ]);

        $users = User::where('email', 'like', '%' . $request->email . '%')
            ->where('id', '!=', $request->user()->id)
            ->limit(10)
            ->get(['id', 'name', 'email', 'avatar']);

        return response()->json([
            'users' => $users,
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user->fresh(),
        ]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'currency_preference' => ['sometimes', 'string', 'size:3'],
            'timezone' => ['sometimes', 'string', 'max:50'],
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Settings updated successfully',
            'user' => $user->fresh(),
        ]);
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'max:2048'],
        ]);

        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        return response()->json([
            'message' => 'Avatar uploaded successfully',
            'avatar_url' => Storage::disk('public')->url($path),
        ]);
    }
}
