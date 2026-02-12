<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Evenement;
use Illuminate\Http\Request;

class EvenementController extends Controller
{
    public function index()
    {
        return Evenement::where('statut', Evenement::STATUT_ACTIF)->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titre' => 'required|string',
            'description' => 'nullable|string',
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'lieu' => 'nullable|string',
            'id_responsable' => 'nullable|exists:users,id'
        ]);

        $responsableId = $request->user()?->id ?? ($validated['id_responsable'] ?? null);

        if (!$responsableId) {
            return response()->json([
                'message' => 'Responsable manquant'
            ], 422);
        }

        $event = Evenement::create([
            'titre' => $validated['titre'],
            'description' => $validated['description'] ?? null,
            'date_debut' => $validated['date_debut'],
            'date_fin' => $validated['date_fin'] ?? null,
            'lieu' => $validated['lieu'] ?? null,
            'id_responsable' => $responsableId,
            'statut' => Evenement::STATUT_PENDING
        ]);

        return response()->json($event, 201);
    }

    public function show($id)
    {
        $event = Evenement::where('statut', Evenement::STATUT_ACTIF)->find($id);

        if (!$event) {
            return response()->json([
                'message' => 'Événement introuvable ou en attente de validation'
            ], 404);
        }

        return $event;
    }

    public function update(Request $request, $id)
    {
        $event = Evenement::findOrFail($id);

        $validated = $request->validate([
            'titre' => 'sometimes|required|string',
            'description' => 'nullable|string',
            'date_debut' => 'sometimes|required|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'lieu' => 'nullable|string',
            'id_responsable' => 'nullable|exists:users,id'
        ]);

        $event->update($validated);

        return $event;
    }

    public function destroy($id)
    {
        Evenement::destroy($id);

        return response()->json([
            'message' => 'Événement supprimé'
        ]);
    }
}
