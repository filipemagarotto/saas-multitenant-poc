<?php

namespace App\Tenancy;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Use este trait em qualquer model que pertenca a um tenant (ex.: Pet).
 *
 * Ele faz, automaticamente:
 *  1) FILTRAR toda consulta pelo tenant atual (global scope) — um tenant
 *     nunca enxerga dados de outro.
 *  2) PREENCHER tenant_id sozinho ao criar um registro novo.
 *
 * A leitura do tenant atual e feita DENTRO das closures (em tempo de
 * consulta/criacao), nunca no boot — assim funciona corretamente mesmo
 * com processos PHP-FPM reaproveitados entre requisicoes.
 */
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        // 1) Filtra todas as queries pelo tenant atual.
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenancy = app(Tenancy::class);

            if ($tenancy->check()) {
                $table = $builder->getModel()->getTable();
                $builder->where($table.'.tenant_id', $tenancy->id());
            }
        });

        // 2) Preenche tenant_id automaticamente ao criar.
        static::creating(function (Model $model) {
            $tenancy = app(Tenancy::class);

            if ($tenancy->check() && empty($model->tenant_id)) {
                $model->tenant_id = $tenancy->id();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
