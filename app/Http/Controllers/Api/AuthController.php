<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class AuthController extends Controller
{
        public function login(Request $request)
{
    $request->validate([
        'email' => 'required',
        'password' => 'required'
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json([
            'message' => 'Compte introuvable'
        ], 401);
    }

    // 👇 BLOQUER SI PAS ACTIF
    if ($user->statut !== 'actif') {
        return response()->json([
            'message' => 'Compte non validé par l’administrateur'
        ], 403);
    }

    if (!Hash::check($request->password, $user->password)) {
        return response()->json([
            'message' => 'Mot de passe incorrect'
        ], 401);
    }

    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'user' => $user,
        'token' => $token
    ]);
}


    // LOGOUT
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Déconnexion réussie'
        ]);
    }
}
