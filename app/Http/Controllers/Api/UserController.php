<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        return User::with('role')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required',
            'prenom' => 'nullable',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'telephone' => 'nullable',
            'role_id' => 'nullable|exists:roles,id',
            'statut' => 'nullable|string'
        ]);

        $roleId = $validated['role_id'] ?? Role::where('nom_role', 'Adherent')->value('id');
        if (!$roleId) {
            $roleId = Role::create(['nom_role' => 'Adherent'])->id;
        }

        $user = User::create([
            'nom' => $validated['nom'],
            'prenom' => $validated['prenom'] ?? null,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'telephone' => $validated['telephone'] ?? null,
            'role_id' => $roleId,
            'statut' => $validated['statut'] ?? 'pending'
        ]);

        return response()->json($user, 201);
    }

    public function show($id)
    {
        return User::with('role')->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'nom' => 'sometimes|required',
            'prenom' => 'nullable',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|required|min:6',
            'telephone' => 'nullable',
            'role_id' => 'nullable|exists:roles,id',
            'statut' => 'nullable|string'
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return $user;
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'message' => 'Utilisateur supprimé'
        ]);
    }
}
