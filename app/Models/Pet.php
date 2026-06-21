<?php

namespace App\Models;

use App\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Pet extends Model
{
    use BelongsToTenant;

    protected $fillable = ['nome', 'especie', 'tenant_id'];
}
