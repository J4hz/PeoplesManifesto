<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCitizenHasWard
{
    public function handle(Request $request, Closure $next): Response
    {
        $citizen = $request->user('citizen');

        if ($citizen !== null && $citizen->ward_id === null) {
            return redirect()->route('citizen.ward.choose');
        }

        return $next($request);
    }
}
