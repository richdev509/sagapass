<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('sagapass-logo.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('sagapass-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('sagapass-logo.png') }}">

    <title>@yield('title', 'Admin Panel') - SAGAPASS</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f6fa;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 260px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            overflow-y: auto;
            overflow-x: hidden;
        }

        /* Scrollbar personnalisé pour le sidebar */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }

        .sidebar .logo {
            padding: 0 20px 30px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }

        .sidebar .logo h3 {
            font-weight: 700;
            font-size: 24px;
            color: white;
        }

        .sidebar .logo small {
            color: rgba(255,255,255,0.8);
            font-size: 13px;
        }

        .sidebar .nav-item {
            padding: 0;
            margin: 5px 10px;
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 15px;
            border-radius: 8px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.15);
            color: white;
            font-weight: 600;
        }

        .sidebar .nav-link i {
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            min-height: 100vh;
            padding: 0;
        }

        /* Top Navbar */
        .top-navbar {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .page-title h4 {
            margin: 0;
            color: #2d3748;
            font-weight: 600;
        }

        .page-title p {
            margin: 0;
            color: #718096;
            font-size: 14px;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .admin-info {
            text-align: right;
        }

        .admin-name {
            font-weight: 600;
            color: #2d3748;
            font-size: 14px;
            display: block;
        }

        .admin-role {
            font-size: 12px;
            color: #718096;
        }

        .admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .dropdown-toggle::after {
            display: none;
        }

        /* Content Area */
        .content-area {
            padding: 30px;
        }

        /* Cards */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.3s;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }

        .card-header {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 20px;
            font-weight: 600;
            color: #2d3748;
        }

        /* Stats Cards */
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .stat-icon.blue {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .stat-icon.green {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        .stat-icon.orange {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .stat-icon.purple {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .stat-details h6 {
            margin: 0;
            color: #718096;
            font-size: 13px;
            font-weight: 500;
        }

        .stat-details h3 {
            margin: 5px 0 0;
            color: #2d3748;
            font-weight: 700;
        }

        /* Buttons */
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
        }

        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        /* Badges */
        .badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 500;
        }

        /* Tables */
        .table {
            background: white;
        }

        .table thead th {
            border-bottom: 2px solid #e2e8f0;
            color: #2d3748;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            padding: 15px;
        }

        .table tbody td {
            padding: 15px;
            vertical-align: middle;
        }

        /* Alerts */
        .alert {
            border: none;
            border-radius: 10px;
        }
    </style>

    @yield('styles')
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <h3><i class="fas fa-shield-alt"></i> SAGAPASS</h3>
            <small>Panneau d'Administration</small>
        </div>

        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                    <i class="fas fa-chart-line"></i>
                    <span>Tableau de Bord</span>
                </a>
            </li>

            @can('verify-documents', 'admin')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.verification.*') ? 'active' : '' }}"
                   href="#verificationSubmenu"
                   data-bs-toggle="collapse"
                   aria-expanded="{{ request()->routeIs('admin.verification.*') ? 'true' : 'false' }}">
                    <i class="fas fa-file-check"></i>
                    <span>Vérification Documents</span>
                    <i class="fas fa-chevron-down ms-auto" style="font-size: 12px;"></i>
                </a>
                <div class="collapse {{ request()->routeIs('admin.verification.*') ? 'show' : '' }}" id="verificationSubmenu">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.verification.index') ? 'active' : '' }}" href="{{ route('admin.verification.index') }}">
                                <i class="fas fa-clock"></i>
                                <span>En Attente</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.verification.verified') ? 'active' : '' }}" href="{{ route('admin.verification.verified') }}">
                                <i class="fas fa-check-circle"></i>
                                <span>Vérifiés</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.verification.rejected') ? 'active' : '' }}" href="{{ route('admin.verification.rejected') }}">
                                <i class="fas fa-times-circle"></i>
                                <span>Rejetés</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.video-verification.*') ? 'active' : '' }}"
                   href="#videoVerificationSubmenu"
                   data-bs-toggle="collapse"
                   aria-expanded="{{ request()->routeIs('admin.video-verification.*') ? 'true' : 'false' }}">
                    <i class="fas fa-video"></i>
                    <span>Vérification Vidéos</span>
                    <i class="fas fa-chevron-down ms-auto" style="font-size: 12px;"></i>
                </a>
                <div class="collapse {{ request()->routeIs('admin.video-verification.*') ? 'show' : '' }}" id="videoVerificationSubmenu">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.video-verification.index') ? 'active' : '' }}" href="{{ route('admin.video-verification.index') }}">
                                <i class="fas fa-clock"></i>
                                <span>En Attente</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.video-verification.approved') ? 'active' : '' }}" href="{{ route('admin.video-verification.approved') }}">
                                <i class="fas fa-check-circle"></i>
                                <span>Approuvées</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.video-verification.rejected') ? 'active' : '' }}" href="{{ route('admin.video-verification.rejected') }}">
                                <i class="fas fa-times-circle"></i>
                                <span>Rejetées</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            @endcan

            @can('view-users', 'admin')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.citizens.*') ? 'active' : '' }}" href="{{ route('admin.citizens.index') }}">
                    <i class="fas fa-users"></i>
                    <span>Gestion Citoyens</span>
                </a>
            </li>
            @endcan

            @can('view-admins', 'admin')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.admins.*') ? 'active' : '' }}" href="{{ route('admin.admins.index') }}">
                    <i class="fas fa-users-cog"></i>
                    <span>Gestion Admins</span>
                </a>
            </li>
            @endcan

            @can('view-roles', 'admin')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.roles.*', 'admin.permissions.*') ? 'active' : '' }}" href="{{ route('admin.roles.index') }}">
                    <i class="fas fa-user-shield"></i>
                    <span>Rôles & Permissions</span>
                </a>
            </li>
            @endcan

            @can('view-audit-logs', 'admin')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.audit-logs') ? 'active' : '' }}" href="{{ route('admin.audit-logs') }}">
                    <i class="fas fa-history"></i>
                    <span>Logs d'Audit</span>
                </a>
            </li>
            @endcan

            @can('view-oauth-apps', 'admin')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.oauth.*') ? 'active' : '' }}" href="{{ route('admin.oauth.index') }}">
                    <i class="fas fa-plug"></i>
                    <span>Applications OAuth</span>
                    @php
                        $pendingOAuthCount = \App\Models\DeveloperApplication::where('status', 'pending')->count();
                    @endphp
                    @if($pendingOAuthCount > 0)
                        <span class="badge bg-warning ms-auto">{{ $pendingOAuthCount }}</span>
                    @endif
                </a>
            </li>
            @endcan

            @can('view-statistics', 'admin')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.statistics') ? 'active' : '' }}" href="{{ route('admin.statistics') }}">
                    <i class="fas fa-chart-bar"></i>
                    <span>Statistiques Avancées</span>
                </a>
            </li>
            @endcan

            @can('view-security-logs', 'admin')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.security.*') ? 'active' : '' }}" href="{{ route('admin.security.dashboard') }}">
                    <i class="fas fa-shield-alt"></i>
                    <span>Sécurité</span>
                </a>
            </li>
            @endcan

            @can('manage-settings', 'admin')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.index') }}">
                    <i class="fas fa-cogs"></i>
                    <span>Paramètres Système</span>
                </a>
            </li>
            @endcan

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.two-factor.*') ? 'active' : '' }}" href="{{ route('admin.two-factor.index') }}">
                    <i class="fas fa-lock"></i>
                    <span>Authentification 2FA</span>
                </a>
            </li>

            <li class="nav-item mt-4">
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="nav-link border-0 bg-transparent w-100 text-start">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Déconnexion</span>
                    </button>
                </form>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <div class="top-navbar">
            <div class="page-title">
                <h4>@yield('page-title', 'Tableau de Bord')</h4>
                <p>@yield('page-subtitle', 'Bienvenue dans le panneau d\'administration')</p>
            </div>

            <div class="admin-profile">
                <div class="admin-info">
                    <span class="admin-name">{{ Auth::guard('admin')->user()->name }}</span>
                    <span class="admin-role">
                        @if(Auth::guard('admin')->user()->roles->isNotEmpty())
                            {{ Auth::guard('admin')->user()->roles->first()->name }}
                        @else
                            Administrateur
                        @endif
                    </span>
                </div>
                <div class="admin-avatar">
                    {{ strtoupper(substr(Auth::guard('admin')->user()->name, 0, 1)) }}{{ strtoupper(substr(Auth::guard('admin')->user()->name, 1, 1)) }}
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    @yield('scripts')
</body>
</html>
