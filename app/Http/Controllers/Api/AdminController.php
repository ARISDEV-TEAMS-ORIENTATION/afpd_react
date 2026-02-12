<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Evenement;
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

    // VALIDER ÉVÉNEMENT
    public function validateEvent($id)
    {
        $event = Evenement::findOrFail($id);

        $event->update([
            'statut' => Evenement::STATUT_ACTIF
        ]);

        return response()->json([
            'message' => 'Événement validé',
            'evenement' => $event
        ]);
    }

    // REFUSER ÉVÉNEMENT
    public function rejectEvent($id)
    {
        $event = Evenement::findOrFail($id);

        $event->update([
            'statut' => Evenement::STATUT_REFUSE
        ]);

        return response()->json([
            'message' => 'Événement refusé',
            'evenement' => $event
        ]);
    }

    // RÉCUPÉRER LES ÉVÉNEMENTS EN ATTENTE
    public function pendingEvents()
    {
        $events = Evenement::with('responsable')
            ->where('statut', Evenement::STATUT_PENDING)
            ->get();

        return response()->json($events);
    }
}
