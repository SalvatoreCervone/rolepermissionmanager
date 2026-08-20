<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — ACL Manager Preview</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: #0f111a;
            color: #e4e6f0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .login-card {
            background: #1e2235;
            border: 1px solid #2a2e45;
            border-radius: 16px;
            padding: 48px 40px;
            width: 420px;
            box-shadow: 0 8px 48px rgba(0, 0, 0, 0.4);
        }
        .login-card h1 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #6c5ce7, #74b9ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .login-card .subtitle {
            font-size: 14px;
            color: #8b8fa8;
            margin-bottom: 32px;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #8b8fa8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .form-control {
            width: 100%;
            padding: 12px 16px;
            background: #252940;
            border: 1px solid #2a2e45;
            border-radius: 8px;
            color: #e4e6f0;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.2s ease;
        }
        .form-control:focus {
            outline: none;
            border-color: #6c5ce7;
            box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.15);
        }
        .btn-login {
            width: 100%;
            padding: 14px;
            background: #6c5ce7;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: inherit;
            margin-top: 8px;
        }
        .btn-login:hover { background: #7c6ef7; transform: translateY(-1px); }
        .error {
            background: rgba(225, 112, 85, 0.15);
            color: #e17055;
            border: 1px solid rgba(225, 112, 85, 0.3);
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .hint {
            margin-top: 24px;
            padding: 16px;
            background: rgba(108, 92, 231, 0.08);
            border: 1px solid rgba(108, 92, 231, 0.2);
            border-radius: 8px;
            font-size: 13px;
            color: #8b8fa8;
        }
        .hint code {
            background: #252940;
            padding: 2px 6px;
            border-radius: 4px;
            color: #74b9ff;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h1>🛡️ ACL Manager</h1>
        <p class="subtitle">Preview Environment — Login to access the admin panel</p>

        @if($errors->any())
            <div class="error">
                @foreach($errors->all() as $error)
                    {{ $error }}
                @endforeach
            </div>
        @endif

        <form method="POST" action="/login">
            @csrf
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" value="admin@demo.test" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" value="password" required>
            </div>
            <button type="submit" class="btn-login">Sign In</button>
        </form>

        <div class="hint">
            <strong>Demo Credentials:</strong><br>
            Email: <code>admin@demo.test</code><br>
            Password: <code>password</code>
        </div>
    </div>
</body>
</html>
