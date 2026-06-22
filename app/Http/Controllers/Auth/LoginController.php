<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Tenancy\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Login/logout por tenant.
 *
 * O isolamento NAO esta aqui: ele vem do trait BelongsToTenant no model User.
 * Como o middleware 'tenant' ja definiu o tenant atual antes desta rota, toda
 * busca de usuario (Auth::attempt e a recuperacao do usuario da sessao) ja vem
 * filtrada por tenant_id. Logo, so e possivel autenticar com um usuario DESTE
 * tenant, e uma sessao de um tenant nunca resolve um usuario de outro.
 */
class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login', [
            'tenant' => app(Tenancy::class)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Credenciais invalidas para este tenant.',
            ]);
        }

        // Evita fixacao de sessao apos autenticar.
        $request->session()->regenerate();

        return redirect()->intended('/pets');
    }

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
