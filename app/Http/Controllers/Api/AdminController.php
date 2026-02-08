<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;

class AdminController extends Controller
{
    // VALIDER
    public function validateUser($id)
    {
        $user = User::findOrFail($id);

        $user->update([
            'statut' => 'actif'
        ]);

        return response()->json([
            'message' => 'Utilisateur validé'
        ]);
    }

    // REFUSER
    public function rejectUser($id)
    {
        $user = User::findOrFail($id);

        $user->delete();

        return response()->json([
            'message' => 'Utilisateur supprimé'
        ]);
    }

    // RÉCUPÉRER UTILISATEURS EN ATTENTE
    public function pendingUsers()
    {
        $users = User::with('role')
            ->where('statut', 'pending')
            ->get();

        return response()->json($users);
    }
}
