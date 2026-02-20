<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SchoolHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #F8F9FC 0%, #E5E7EB 100%);
        }
        
        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 40px;
        }
        
        .login-card {
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 1.75rem;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 10px;
        }
        
        .login-logo i {
            color: #7C3AED;
            font-size: 2rem;
        }
        
        .login-logo .version {
            font-size: 0.65rem;
            background: #FFE066;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: 500;
        }
        
        .login-subtitle {
            color: #6B7280;
            font-size: 0.9rem;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 500;
            color: #1F2937;
            margin-bottom: 8px;
        }
        
        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #E5E7EB;
            border-radius: 10px;
            font-size: 0.95rem;
            color: #1F2937;
            transition: all 0.2s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #7C3AED;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }
        
        .form-check {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 25px;
        }
        
        .form-check input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #7C3AED;
        }
        
        .form-check label {
            font-size: 0.85rem;
            color: #6B7280;
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: #7C3AED;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-login:hover {
            background: #6D28D9;
            transform: translateY(-1px);
        }
        
        .error-message {
            background: #FEE2E2;
            color: #DC2626;
            padding: 12px 15px;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 20px;
        }
        
        .footer-text {
            text-align: center;
            margin-top: 25px;
            font-size: 0.8rem;
            color: #9CA3AF;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <i class="fas fa-graduation-cap"></i>
                    <span>SchoolHub</span>
                    <span class="version">v2.2</span>
                </div>
                <p class="login-subtitle">Sistema de Gestão de Alunos</p>
            </div>
            
            @if($errors->any())
            <div class="error-message">
                {{ $errors->first() }}
            </div>
            @endif
            
            <form method="POST" action="{{ route('login') }}">
                @csrf
                
                <div class="form-group">
                    <label class="form-label" for="email">E-mail</label>
                    <input type="email" 
                           class="form-control" 
                           id="email" 
                           name="email" 
                           value="{{ old('email') }}" 
                           placeholder="seu@email.com"
                           required 
                           autofocus>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">Senha</label>
                    <input type="password" 
                           class="form-control" 
                           id="password" 
                           name="password" 
                           placeholder="••••••••"
                           required>
                </div>
                
                <div class="form-check">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Lembrar de mim</label>
                </div>
                
                <button type="submit" class="btn-login">
                    Entrar
                </button>
            </form>
            
            <p class="footer-text">
                © {{ date('Y') }} SchoolHub. Todos os direitos reservados.
            </p>
        </div>
    </div>
</body>
</html>
