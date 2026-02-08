<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Annonce;
use Illuminate\Http\Request;

class AnnonceController extends Controller
{
    public function index()
    {
        return Annonce::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titre' => 'required|string',
            'contenu' => 'required|string',
            'id_auteur' => 'nullable|exists:users,id',
            'statut' => 'nullable|string'
        ]);

        $auteurId = $request->user()?->id ?? ($validated['id_auteur'] ?? null);
        if (!$auteurId) {
            return response()->json([
                'message' => 'Auteur manquant'
            ], 422);
        }

        $annonce = Annonce::create([
            'titre' => $validated['titre'],
            'contenu' => $validated['contenu'],
            'id_auteur' => $auteurId,
            'statut' => $validated['statut'] ?? 'publie'
        ]);

        return response()->json($annonce, 201);
    }

    public function show($id)
    {
        return Annonce::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $annonce = Annonce::findOrFail($id);

        $validated = $request->validate([
            'titre' => 'sometimes|required|string',
            'contenu' => 'sometimes|required|string',
            'statut' => 'nullable|string'
        ]);

        $annonce->update($validated);

        return $annonce;
    }

    public function destroy($id)
    {
        Annonce::destroy($id);

        return response()->json([
            'message' => 'Annonce supprimée'
        ]);
    }
}
