<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Authentification requise',
            ], 401);
        }

        if (count($roles) === 0) {
            return $next($request);
        }

        $roleName = mb_strtolower((string) $user?->role?->nom_role);
        $allowedRoles = array_map(
            static fn (string $role): string => mb_strtolower($role),
            $roles
        );

        if (!in_array($roleName, $allowedRoles, true)) {
            return response()->json([
                'message' => 'Accès interdit pour ce rôle',
            ], 403);
        }

        return $next($request);
    }
}
