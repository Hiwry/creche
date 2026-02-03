<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'SchoolHub' }} - Sistema de Gestão</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-graduation-cap"></i>
                    <span>SchoolHub</span>
                    <span class="version">v1.0</span>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <span class="nav-section-title">MENU</span>
                    
                    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="fas fa-th-large"></i>
                        <span>Dashboard</span>
                    </a>
                    
                    <a href="{{ route('students.index') }}" class="nav-link {{ request()->routeIs('students.*') ? 'active' : '' }}">
                        <i class="fas fa-user-graduate"></i>
                        <span>Alunos</span>
                    </a>
                    
                    <a href="{{ route('classes.index') }}" class="nav-link {{ request()->routeIs('classes.*') ? 'active' : '' }}">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span>Turmas</span>
                    </a>
                    
                    <a href="{{ route('attendance.index') }}" class="nav-link {{ (request()->routeIs('attendance.*') && !request()->routeIs('attendance.extra-hours')) ? 'active' : '' }}">
                        <i class="fas fa-clock"></i>
                        <span>Entrada & Saída</span>
                    </a>
                    
                    <a href="{{ route('financial.index') }}" class="nav-link {{ request()->routeIs('financial.*') ? 'active' : '' }}">
                        <i class="fas fa-dollar-sign"></i>
                        <span>Faturas</span>
                    </a>
                    
                    <a href="{{ route('expenses.index') }}" class="nav-link {{ request()->routeIs('expenses.*') ? 'active' : '' }}">
                        <i class="fas fa-receipt"></i>
                        <span>Despesas</span>
                    </a>
                    
                    <a href="{{ route('attendance.extra-hours') }}" class="nav-link {{ request()->routeIs('attendance.extra-hours') ? 'active' : '' }}">
                        <i class="fas fa-chart-bar"></i>
                        <span>Relatórios</span>
                    </a>
                    
                    <a href="{{ route('reports.birthdays') }}" class="nav-link {{ request()->routeIs('reports.birthdays') ? 'active' : '' }}">
                        <i class="fas fa-cake-candles"></i>
                        <span>Aniversariantes</span>
                    </a>

                    <div class="nav-section-title" style="margin-top: 20px;">MATERIAIS</div>
                    <a href="{{ route('school-materials.index') }}" class="nav-link {{ request()->routeIs('school-materials.index') ? 'active' : '' }}">
                        <i class="fas fa-list-ul"></i>
                        <span>Lista de Materiais</span>
                    </a>
                    
                    @if(auth()->user()->isAdmin())
                    <a href="{{ route('settings.index') }}" class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                        <i class="fas fa-cog"></i>
                        <span>Configurações</span>
                    </a>
                    @endif
                </div>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="main-header">
                <div class="header-search">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Buscar alunos, turmas..." id="global-search">
                </div>
                
                <div class="header-actions">

                    <button class="header-btn">
                        <i class="fas fa-bell"></i>
                    </button>
                    
                    <div class="user-menu">
                        <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="user-avatar">
                        <div class="user-info">
                            <span class="user-name">{{ auth()->user()->name }}</span>
                            <span class="user-role">{{ auth()->user()->role_label }}</span>
                        </div>
                        <div class="user-dropdown">
                            <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="fas fa-sign-out-alt"></i> Sair
                            </a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                @csrf
                            </form>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Alerts -->
            @if(session('success'))
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                {{ session('success') }}
                <button type="button" class="alert-close" onclick="this.parentElement.remove()">×</button>
            </div>
            @endif
            
            @if(session('error'))
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                {{ session('error') }}
                <button type="button" class="alert-close" onclick="this.parentElement.remove()">×</button>
            </div>
            @endif
            
            @if(session('info'))
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                {{ session('info') }}
                <button type="button" class="alert-close" onclick="this.parentElement.remove()">×</button>
            </div>
            @endif
            
            <!-- Page Content -->
            <div class="page-content">
                @yield('content')
            </div>
        </main>
    </div>
    
    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>
