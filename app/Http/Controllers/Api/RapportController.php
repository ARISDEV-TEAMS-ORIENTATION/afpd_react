<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rapport;
use Illuminate\Http\Request;

class RapportController extends Controller
{
    public function index()
    {
        return Rapport::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type_rapport' => 'required|string',
            'periode' => 'required|string',
            'chemin_fichier' => 'nullable|string',
            'id_createur' => 'nullable|exists:users,id'
        ]);

        $createurId = $request->user()?->id ?? ($validated['id_createur'] ?? null);

        $rapport = Rapport::create([
            'type_rapport' => $validated['type_rapport'],
            'periode' => $validated['periode'],
            'chemin_fichier' => $validated['chemin_fichier'] ?? null,
            'id_createur' => $createurId
        ]);

        return response()->json($rapport, 201);
    }

    public function show($id)
    {
        return Rapport::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $rapport = Rapport::findOrFail($id);

        $validated = $request->validate([
            'type_rapport' => 'sometimes|required|string',
            'periode' => 'sometimes|required|string',
            'chemin_fichier' => 'nullable|string'
        ]);

        $rapport->update($validated);

        return $rapport;
    }

    public function destroy($id)
    {
        Rapport::destroy($id);

        return response()->json([
            'message' => 'Rapport supprimé'
        ]);
    }
}
