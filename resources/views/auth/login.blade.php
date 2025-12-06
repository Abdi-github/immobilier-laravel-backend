<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Immobilier</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, sans-serif; background: #f1f5f9; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); padding: 2.5rem; width: 100%; max-width: 420px; }
        h1 { font-size: 1.5rem; margin-bottom: 0.25rem; }
        .sub { color: #64748b; font-size: 0.875rem; margin-bottom: 1.5rem; }
        label { display: block; font-weight: 500; font-size: 0.875rem; margin-bottom: 0.25rem; }
        input[type="email"], input[type="password"] { width: 100%; padding: 0.625rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 1rem; margin-bottom: 1rem; }
        .remember { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem; font-size: 0.875rem; }
        button { width: 100%; padding: 0.75rem; background: #2563eb; color: #fff; border: none; border-radius: 6px; font-size: 1rem; font-weight: 500; cursor: pointer; }
        button:hover { background: #1d4ed8; }
        .error { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem; font-size: 0.875rem; }
        .field-error { color: #dc2626; font-size: 0.75rem; margin-top: -0.75rem; margin-bottom: 0.75rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Admin Panel</h1>
        <p class="sub">Sign in to Immobilier administration</p>

        @if ($errors->any())
            <div class="error">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @if (session('status'))
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a;padding:0.75rem;border-radius:6px;margin-bottom:1rem;font-size:0.875rem;">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus data-testid="login-email">

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required data-testid="login-password">

            <div class="remember">
                <input type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                <label for="remember" style="margin-bottom:0">Remember me</label>
            </div>

            <button type="submit" data-testid="login-button">Sign In</button>
        </form>
    </div>
</body>
</html>
