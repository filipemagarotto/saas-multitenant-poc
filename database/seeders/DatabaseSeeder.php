<?php

namespace Database\Seeders;

use App\Models\Pet;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Cria os tenants de exemplo, um usuario admin por tenant e alguns pets.
     *
     * Idempotente (firstOrCreate): pode rodar varias vezes sem duplicar.
     * Senha padrao dos usuarios de exemplo: "password".
     */
    public function run(): void
    {
        $tenants = [
            [
                'slug' => 'cliente1',
                'name' => 'Clinica Pet Feliz',
                'email' => 'admin@cliente1.test',
                'pets' => [
                    ['nome' => 'Rex', 'especie' => 'Cachorro'],
                    ['nome' => 'Mingau', 'especie' => 'Gato'],
                ],
            ],
            [
                'slug' => 'cliente2',
                'name' => 'Aumiga Veterinaria',
                'email' => 'admin@cliente2.test',
                'pets' => [
                    ['nome' => 'Thor', 'especie' => 'Cachorro'],
                    ['nome' => 'Luna', 'especie' => 'Gato'],
                ],
            ],
        ];

        foreach ($tenants as $data) {
            $tenant = Tenant::firstOrCreate(
                ['slug' => $data['slug']],
                ['name' => $data['name']],
            );

            // Usuario admin do tenant. O scope do BelongsToTenant nao atrapalha
            // no CLI (nao ha tenant atual), por isso passamos tenant_id explicito.
            User::firstOrCreate(
                ['tenant_id' => $tenant->id, 'email' => $data['email']],
                [
                    'name' => 'Admin '.$tenant->name,
                    'password' => Hash::make('password'),
                ],
            );

            foreach ($data['pets'] as $pet) {
                Pet::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'nome' => $pet['nome']],
                    ['especie' => $pet['especie']],
                );
            }
        }
    }
}
