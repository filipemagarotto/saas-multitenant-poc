<?php

namespace Tests\Feature;

use App\Models\Pet;
use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\Tenancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Garante que o login e o isolamento de dados sao por tenant:
 * autenticar-se no cliente1 nunca da acesso aos dados do cliente2.
 *
 * Obs.: no ambiente de teste o SESSION_DRIVER e "array" (nao persiste entre
 * requisicoes), por isso validamos cada peca de forma deterministica em vez de
 * simular um fluxo login->navegacao multi-requisicao.
 */
class TenantAuthTest extends TestCase
{
    use RefreshDatabase;

    private string $central = 'tcsystem.shop';

    private function url(string $slug, string $path = '/'): string
    {
        return "http://{$slug}.{$this->central}{$path}";
    }

    /**
     * @return array{0: Tenant, 1: User}
     */
    private function makeTenant(string $slug, string $petName): array
    {
        $tenant = Tenant::create(['name' => ucfirst($slug), 'slug' => $slug]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => "Admin {$slug}",
            'email' => "admin@{$slug}.test",
            'password' => 'secret',
        ]);

        Pet::create(['tenant_id' => $tenant->id, 'nome' => $petName, 'especie' => 'Cachorro']);

        return [$tenant, $user];
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->makeTenant('cliente1', 'Rex');

        $this->get($this->url('cliente1', '/pets'))
            ->assertRedirect('/login');
    }

    public function test_valid_credentials_log_the_user_in(): void
    {
        $this->makeTenant('cliente1', 'Rex');

        $this->post($this->url('cliente1', '/login'), [
            'email' => 'admin@cliente1.test',
            'password' => 'secret',
        ])->assertRedirect('/pets');

        $this->assertAuthenticated();
    }

    public function test_authenticated_user_sees_only_their_tenant_pets(): void
    {
        [, $user1] = $this->makeTenant('cliente1', 'Rex');
        $this->makeTenant('cliente2', 'Thor');

        $response = $this->actingAs($user1)->get($this->url('cliente1', '/pets'));

        $response->assertOk();
        $response->assertSee('Rex');        // pet do proprio tenant
        $response->assertDontSee('Thor');   // pet do outro tenant
    }

    public function test_login_is_scoped_to_the_tenant(): void
    {
        $this->makeTenant('cliente1', 'Rex');
        $this->makeTenant('cliente2', 'Thor');

        // Credenciais validas do cliente1, mas usadas no subdominio do cliente2.
        $this->post($this->url('cliente2', '/login'), [
            'email' => 'admin@cliente1.test',
            'password' => 'secret',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_user_lookup_is_isolated_by_tenant(): void
    {
        [$tenant1, $user1] = $this->makeTenant('cliente1', 'Rex');
        [$tenant2] = $this->makeTenant('cliente2', 'Thor');

        // E esta busca escopada (mesma que a sessao usa para resolver o usuario
        // logado) que impede uma sessao do cliente1 de valer no cliente2.
        app(Tenancy::class)->set($tenant2);
        $this->assertNull(User::find($user1->id));

        app(Tenancy::class)->set($tenant1);
        $this->assertNotNull(User::find($user1->id));
    }
}
