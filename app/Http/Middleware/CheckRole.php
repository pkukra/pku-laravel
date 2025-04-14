<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = Auth::user();

        // Log user dan role yang ada
        Log::info('User Role Check', [
            'user_id' => $user ? $user->id : 'No user',
            'role' => $user && $user->role ? $user->role->name : 'No role',
            'allowed_roles' => $roles
        ]);

        if (!$user || !$user->role) {
            abort(403, 'Akses ditolak.');
        }

        // Jika superadmin, bisa akses semuanya
        if ($user->role->name === 'superadmin') {
            return $next($request);
        }

        if (!in_array($user->role->name, $roles)) {
            Log::warning('Akses Ditolak - Role Tidak Cocok', [
                'user_role' => $user->role->name,
                'allowed_roles' => $roles
            ]);
            abort(403, 'Akses ditolak.');
        }

        return $next($request);
    }
}
