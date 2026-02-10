<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cotisation;

class CotisationController extends Controller
{
    public function index()
    {
        return response()->json(Cotisation::all());
    }

    public function show($id)
    {
        $cotisation = Cotisation::findOrFail($id);
        return response()->json($cotisation);
    }

    public function store(Request $request)
    {
        // $actor = $request->user();

        // $allowedId = env('COTISATION_COLLECTOR_ID');
        // $isAdmin = optional($actor->role)->nom_role === 'Tresoriere';

        // if (! $actor || (!empty($allowedId) && $actor->id != (int) $allowedId) && ! $isAdmin) {
        //     return response()->json(['message' => 'Non autorisé'], 403);
        // }

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'montant' => 'required|numeric',
        ]);

        $cotisation = Cotisation::create($data);
        return response()->json($cotisation, 201);
    }

    public function update(Request $request, $id)
    {
        $cotisation = Cotisation::findOrFail($id);

        $data = $request->validate([
            'user_id' => 'sometimes|exists:users,id',
            'montant' => 'sometimes|numeric',
        ]);

        $cotisation->update($data);
        return response()->json($cotisation);
    }

    public function destroy($id)
    {
        $cotisation = Cotisation::findOrFail($id);
        $cotisation->delete();
        return response()->json(null, 204);
    }
}
