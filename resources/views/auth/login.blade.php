<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar — {{ $tenant->name }}</title>
    <style>
        body { font-family: sans-serif; max-width: 360px; margin: 60px auto; padding: 0 16px; }
        h1 { font-size: 1.25rem; }
        .tag { background:#eef; padding:2px 8px; border-radius:4px; }
        label { display:block; margin: 12px 0 4px; font-size: .9rem; }
        input[type=email], input[type=password] {
            width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;
        }
        button { margin-top: 16px; padding: 8px 16px; border: 0; border-radius: 4px; background:#3b5bdb; color:#fff; cursor:pointer; }
        .erro { background:#fde; color:#900; padding:8px 12px; border-radius:4px; margin: 12px 0; font-size: .9rem; }
        .check { margin-top: 12px; font-size: .9rem; }
    </style>
</head>
<body>
    <h1>🔐 Entrar em <span class="tag">{{ $tenant->name }}</span></h1>
    <p>Tenant: <code>{{ $tenant->slug }}</code></p>

    @if ($errors->any())
        <div class="erro">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="/login">
        @csrf

        <label for="email">E-mail</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>

        <label for="password">Senha</label>
        <input id="password" type="password" name="password" required>

        <label class="check"><input type="checkbox" name="remember" value="1"> Lembrar de mim</label>

        <button type="submit">Entrar</button>
    </form>
</body>
</html>
