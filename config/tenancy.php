<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dominio central
    |--------------------------------------------------------------------------
    |
    | Dominio raiz da aplicacao. As rotas "centrais" respondem nele
    | (ex.: landing page), e cada tenant responde num subdominio dele
    | (ex.: cliente1.tcsystem.shop).
    |
    */

    'central_domain' => env('TENANT_CENTRAL_DOMAIN', 'tcsystem.shop'),

];
