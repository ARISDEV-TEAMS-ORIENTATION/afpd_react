<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return response()->json($request->user()->load('role'));
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'nom' => 'sometimes|required|string|max:255',
            'prenom' => 'nullable|string|max:255',
            'telephone' => 'nullable|string|max:50',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
        ]);

        $user->update($validated);

        return response()->json($user->fresh()->load('role'));
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Mot de passe actuel incorrect',
            ], 422);
        }

        $user->update([
            'password' => $validated['password'],
        ]);

        return response()->json([
            'message' => 'Mot de passe mis à jour',
        ]);
    }

    public function revokeToken(Request $request, int $tokenId)
    {
        $deleted = $request->user()
            ->tokens()
            ->whereKey($tokenId)
            ->delete();

        if (!$deleted) {
            return response()->json([
                'message' => 'Token introuvable',
            ], 404);
        }

        return response()->json([
            'message' => 'Token révoqué',
        ]);
    }

    public function updateAvatar(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'avatar' => 'required|file|image|max:2048',
        ]);

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $avatarPath = $validated['avatar']->store('avatars', 'public');

        $user->update([
            'avatar_path' => $avatarPath,
        ]);

        return response()->json([
            'message' => 'Avatar mis à jour',
            'user' => $user->fresh()->load('role'),
        ]);
    }

    public function deleteAvatar(Request $request)
    {
        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->update([
            'avatar_path' => null,
        ]);

        return response()->json([
            'message' => 'Avatar supprimé',
            'user' => $user->fresh()->load('role'),
        ]);
    }

    public function preferences(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'theme' => $user->theme,
            'language' => $user->language,
            'timezone' => $user->timezone,
            'email_notifications' => (bool) $user->email_notifications,
            'push_notifications' => (bool) $user->push_notifications,
        ]);
    }

    public function updatePreferences(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'theme' => ['sometimes', Rule::in(['light', 'dark', 'system'])],
            'language' => 'sometimes|string|max:10',
            'timezone' => 'sometimes|timezone',
            'email_notifications' => 'sometimes|boolean',
            'push_notifications' => 'sometimes|boolean',
        ]);

        $user->update($validated);
        $freshUser = $user->fresh();

        return response()->json([
            'message' => 'Préférences mises à jour',
            'preferences' => [
                'theme' => $freshUser->theme,
                'language' => $freshUser->language,
                'timezone' => $freshUser->timezone,
                'email_notifications' => (bool) $freshUser->email_notifications,
                'push_notifications' => (bool) $freshUser->push_notifications,
            ],
        ]);
    }

    public function updatePrivacy(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'show_phone' => 'sometimes|boolean',
            'show_email' => 'sometimes|boolean',
            'profile_visibility' => ['sometimes', Rule::in(['public', 'members', 'private'])],
        ]);

        $user->update($validated);
        $freshUser = $user->fresh();

        return response()->json([
            'message' => 'Paramètres de confidentialité mis à jour',
            'privacy' => [
                'show_phone' => (bool) $freshUser->show_phone,
                'show_email' => (bool) $freshUser->show_email,
                'profile_visibility' => $freshUser->profile_visibility,
            ],
        ]);
    }
}
