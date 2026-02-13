<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        return Role::orderBy('nom_role')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom_role' => 'required|string|unique:roles,nom_role',
            'description' => 'nullable|string'
        ]);

        $role = Role::create([
            'nom_role' => $validated['nom_role'],
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json($role, 201);
    }
}
