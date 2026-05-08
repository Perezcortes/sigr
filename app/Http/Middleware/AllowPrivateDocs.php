<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AllowPrivateDocs
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->environment('production')) {
            return $next($request);
        }

        $expectedToken = (string) env('SWAGGER_DOCS_TOKEN', '');
        $providedToken = (string) $request->header('X-Docs-Token', '');

        if ($expectedToken !== '' && hash_equals($expectedToken, $providedToken)) {
            return $next($request);
        }

        $user = $request->user();
        if ($user !== null && $user->hasRole('Administrador')) {
            return $next($request);
        }

        abort(403, 'Swagger docs are private. Inicia sesión en el panel como Administrador o usa X-Docs-Token.');
    }
}
