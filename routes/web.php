<?php

use App\Http\Controllers\Auth\LoginController;
use App\Models\Pet;
use App\Tenancy\Tenancy;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

$central = config('tenancy.central_domain');

/*
|--------------------------------------------------------------------------
| Rotas CENTRAIS (dominio raiz: tcsystem.shop)
|--------------------------------------------------------------------------
| Aqui ficaria a landing page, cadastro de novos tenants, etc.
*/
Route::domain($central)->group(function () {
    Route::get('/', function () {
        return view('welcome');
    });
});

/*
|--------------------------------------------------------------------------
| Rotas de TENANT (subdominios: cliente1.tcsystem.shop)
|--------------------------------------------------------------------------
| O middleware 'tenant' identifica o tenant pelo subdominio e o define como
| atual. A partir dai, Pet::all() ja retorna SO os pets daquele tenant, e o
| login (User tem BelongsToTenant) tambem fica restrito a este tenant.
*/
Route::domain('{tenant}.'.$central)->middleware('tenant')->group(function () use ($central) {

    // --- Autenticacao (acessivel sem login) ---
    Route::get('/login', [LoginController::class, 'show']);
    Route::post('/login', [LoginController::class, 'store']);
    Route::post('/logout', [LoginController::class, 'destroy']);

    // --- Rotas protegidas: exigem usuario autenticado DESTE tenant ---
    Route::middleware('auth')->group(function () use ($central) {

        Route::get('/', function () {
            $tenant = app(Tenancy::class)->get();
            $user = Auth::user();

            return "Voce esta no tenant: <strong>{$tenant->name}</strong> "
                 . "({$tenant->slug}), logado como <strong>{$user->name}</strong>. "
                 . "Veja <a href='/pets'>/pets</a>.";
        });

        Route::get('/pets', function () use ($central) {
            $tenant = app(Tenancy::class)->get();
            $user = Auth::user();
            $pets = Pet::orderBy('id')->get();
            $csrf = csrf_token();

            $linhas = $pets->map(fn ($p) =>
                "<tr><td>{$p->id}</td><td>{$p->nome}</td><td>{$p->especie}</td></tr>"
            )->implode('');

            return <<<HTML
                <!DOCTYPE html>
                <html lang="pt-br">
                <head><meta charset="utf-8"><title>Pets - {$tenant->name}</title>
                <style>
                    body { font-family: sans-serif; max-width: 600px; margin: 40px auto; }
                    table { border-collapse: collapse; width: 100%; }
                    th, td { border: 1px solid #ccc; padding: 8px 12px; text-align: left; }
                    th { background: #f4f4f4; }
                    .tag { background:#eef; padding:2px 8px; border-radius:4px; }
                    .topo { display:flex; justify-content:space-between; align-items:center; }
                    .topo form { margin:0; }
                </style></head>
                <body>
                    <div class="topo">
                        <span>Logado como <strong>{$user->name}</strong></span>
                        <form method="POST" action="/logout">
                            <input type="hidden" name="_token" value="{$csrf}">
                            <button type="submit">Sair</button>
                        </form>
                    </div>
                    <h1>🐾 Pets de <span class="tag">{$tenant->name}</span> ({$pets->count()})</h1>
                    <p>Subdominio: <code>{$tenant->slug}.{$central}</code> — dados isolados deste tenant.</p>
                    <table>
                        <thead><tr><th>ID</th><th>Nome</th><th>Espécie</th></tr></thead>
                        <tbody>{$linhas}</tbody>
                    </table>
                </body>
                </html>
                HTML;
        });
    });
});
