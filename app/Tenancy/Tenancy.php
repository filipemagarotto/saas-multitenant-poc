<?php

namespace App\Tenancy;

use App\Models\Tenant;

/**
 * Guarda o "tenant atual" durante uma requisicao.
 *
 * Registrado como singleton no container: existe UMA instancia por
 * requisicao, e o Laravel cria um container novo a cada requisicao,
 * entao nao ha risco de um tenant "vazar" para a requisicao seguinte.
 */
class Tenancy
{
    protected ?Tenant $tenant = null;

    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function get(): ?Tenant
    {
        return $this->tenant;
    }

    public function check(): bool
    {
        return $this->tenant !== null;
    }

    public function id(): ?int
    {
        return $this->tenant?->id;
    }
}
