<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stancl\Tenancy\Database\Models\Tenant;
use Symfony\Component\HttpFoundation\Response;

class InitialiseTenantFromUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->tenant_id) {
            $tenant = Tenant::find($user->tenant_id);

            if ($tenant) {
                tenancy()->initialize($tenant);
            }
        }

        return $next($request);
    }
}
