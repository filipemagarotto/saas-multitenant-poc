<?php

use App\Models\Pet;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/pets', function () {
    $pets = Pet::orderBy('id')->get();

    $linhas = $pets->map(fn ($p) =>
        "<tr><td>{$p->id}</td><td>{$p->nome}</td><td>{$p->especie}</td></tr>"
    )->implode('');

    return <<<HTML
        <!DOCTYPE html>
        <html lang="pt-br">
        <head><meta charset="utf-8"><title>Pets</title>
        <style>
            body { font-family: sans-serif; max-width: 600px; margin: 40px auto; }
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #ccc; padding: 8px 12px; text-align: left; }
            th { background: #f4f4f4; }
        </style></head>
        <body>
            <h1>🐾 Pets ({$pets->count()})</h1>
            <p>Dados vindos do MySQL via Laravel.</p>
            <table>
                <thead><tr><th>ID</th><th>Nome</th><th>Espécie</th></tr></thead>
                <tbody>{$linhas}</tbody>
            </table>
        </body>
        </html>
        HTML;
});
