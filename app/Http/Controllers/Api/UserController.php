<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->with('role');

        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('prenom', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role_id')) {
            $query->where('role_id', (int) $request->query('role_id'));
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->query('statut'));
        }

        if ($request->boolean('with_deleted')) {
            $query->withTrashed();
        }

        return response()->json($query->latest()->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'nullable|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'telephone' => 'nullable|string|max:50',
            'role_id' => 'nullable|exists:roles,id',
            'statut' => 'nullable|string|max:50',
        ]);

        $roleId = $validated['role_id'] ?? Role::whereIn('nom_role', ['Adherente', 'Adherent'])->value('id');
        if (!$roleId) {
            $roleId = Role::create(['nom_role' => 'Adherente'])->id;
        }

        $user = User::create([
            'nom' => $validated['nom'],
            'prenom' => $validated['prenom'] ?? null,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'telephone' => $validated['telephone'] ?? null,
            'role_id' => $roleId,
            'statut' => $validated['statut'] ?? 'pending',
            'date_inscription' => now(),
        ]);

        return response()->json($user, 201);
    }

    public function show($id)
    {
        return response()->json(
            User::withTrashed()->with('role')->findOrFail($id)
        );
    }

    public function update(Request $request, $id)
    {
        $user = User::withTrashed()->findOrFail($id);

        $validated = $request->validate([
            'nom' => 'sometimes|required|string|max:255',
            'prenom' => 'nullable|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|required|min:6',
            'telephone' => 'nullable|string|max:50',
            'role_id' => 'nullable|exists:roles,id',
            'statut' => 'nullable|string|max:50',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json(
            User::withTrashed()->with('role')->findOrFail($user->id)
        );
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'message' => 'Utilisateur désactivé',
        ]);
    }

    public function updateStatus(Request $request, int $id)
    {
        $user = User::withTrashed()->findOrFail($id);

        $validated = $request->validate([
            'statut' => ['required', Rule::in(['pending', 'actif', 'inactif', 'suspendu'])],
        ]);

        $user->statut = $validated['statut'];
        $user->save();

        if ($validated['statut'] === 'inactif') {
            $user->delete();
        } elseif ($user->trashed()) {
            $user->restore();
        }

        return response()->json(
            User::withTrashed()->with('role')->findOrFail($user->id)
        );
    }

    public function updateRole(Request $request, int $id)
    {
        $user = User::withTrashed()->findOrFail($id);

        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        $user->update(['role_id' => $validated['role_id']]);

        return response()->json(
            User::withTrashed()->with('role')->findOrFail($user->id)
        );
    }

    public function cotisations(int $id)
    {
        $user = User::withTrashed()->findOrFail($id);

        return response()->json(
            $user->cotisations()->latest()->get()
        );
    }

    public function participations(int $id)
    {
        $user = User::withTrashed()->findOrFail($id);

        return response()->json(
            $user->participations()
                ->with('responsable')
                ->orderByDesc('date_debut')
                ->get()
        );
    }
}
