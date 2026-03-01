<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = Notification::query();

        if (!$this->canManageAll($request->user()) || !$request->boolean('all')) {
            $query->where('user_id', $request->user()->id);
        }

        if ($request->has('lu')) {
            $query->where('lu', $request->boolean('lu'));
        }

        return response()->json(
            $query->latest()->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'user_id' => 'nullable|integer|exists:users,id',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        $targetIds = [];

        if (!empty($validated['user_id'])) {
            $targetIds[] = (int) $validated['user_id'];
        }

        if (!empty($validated['user_ids'])) {
            $targetIds = array_merge($targetIds, $validated['user_ids']);
        }

        if (count($targetIds) === 0) {
            $targetIds = User::query()
                ->where('statut', 'actif')
                ->pluck('id')
                ->all();
        }

        $targetIds = array_values(array_unique(array_map('intval', $targetIds)));

        $now = now();
        $rows = [];

        foreach ($targetIds as $userId) {
            $rows[] = [
                'message' => $validated['message'],
                'user_id' => $userId,
                'lu' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (count($rows) === 0) {
            return response()->json([
                'message' => 'Aucun destinataire trouvé',
                'total' => 0,
            ], 422);
        }

        Notification::insert($rows);

        return response()->json([
            'message' => 'Notifications envoyées',
            'total' => count($rows),
        ], 201);
    }

    public function markRead(Request $request, int $id)
    {
        $notification = Notification::query()
            ->when(
                !$this->canManageAll($request->user()),
                static fn ($query) => $query->where('user_id', $request->user()->id)
            )
            ->findOrFail($id);

        $notification->update(['lu' => true]);

        return response()->json($notification);
    }

    public function markAllRead(Request $request)
    {
        $query = Notification::query();

        if (!$this->canManageAll($request->user()) || !$request->boolean('all')) {
            $query->where('user_id', $request->user()->id);
        }

        $updated = $query
            ->where('lu', false)
            ->update(['lu' => true]);

        return response()->json([
            'message' => 'Notifications marquées comme lues',
            'updated_count' => $updated,
        ]);
    }

    private function canManageAll(User $user): bool
    {
        $role = $user->role?->nom_role;

        return in_array($role, ['Presidente', 'Secretaire', 'Tresoriere'], true);
    }
}
