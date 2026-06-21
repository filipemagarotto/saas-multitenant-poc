<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Tenancy\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Identifica o tenant pelo subdominio da requisicao.
 *
 * As rotas de tenant usam Route::domain('{tenant}.dominio'), entao o
 * subdominio chega aqui como o parametro de rota "tenant".
 */
class IdentifyTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('tenant');

        $tenant = Tenant::where('slug', $slug)->first();

        if (! $tenant) {
            abort(404, "Tenant [{$slug}] nao encontrado.");
        }

        // Define o tenant atual — a partir daqui, models com BelongsToTenant
        // ja filtram/preenchem por este tenant automaticamente.
        app(Tenancy::class)->set($tenant);

        // Remove o parametro "tenant" da rota para as closures/controllers
        // nao precisarem recebe-lo como argumento.
        $request->route()->forgetParameter('tenant');

        return $next($request);
    }
}
