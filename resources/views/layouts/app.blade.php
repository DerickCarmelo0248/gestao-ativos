<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Gestão de Ativos') — Gestão de Ativos</title>

    <link rel="stylesheet" href="{{ asset('css/app.css').'?v=3' }}">
</head>
<body>
<a class="skip-link" href="#main-content">Ir para o conteúdo</a>
<svg xmlns="http://www.w3.org/2000/svg" style="position:absolute;width:0;height:0;overflow:hidden" aria-hidden="true">
<symbol id="icon-monitor" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="13" rx="2"/><path d="M8 21h8M12 16v5M7 7h6"/></symbol>
<symbol id="icon-box" viewBox="0 0 24 24"><path d="m12 3 9 5v9l-9 5-9-5V8l9-5ZM3 8l9 5 9-5M12 13v9M7 5.8l9 5"/></symbol>
<symbol id="icon-refresh" viewBox="0 0 24 24"><path d="M20 7a8 8 0 0 0-14-2L3 8m0-5v5h5M4 17a8 8 0 0 0 14 2l3-3m0 5v-5h-5"/></symbol>
<symbol id="icon-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></symbol>
<symbol id="icon-container" viewBox="0 0 24 24"><path d="M3 5h18v3H3zM5 8l1 11h12l1-11M9 11v5M15 11v5M8 19v2M16 19v2"/></symbol>
<symbol id="icon-check" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></symbol>
<symbol id="icon-search" viewBox="0 0 24 24"><circle cx="10" cy="10" r="6"/><path d="m15 15 6 6"/></symbol>
<symbol id="icon-arrow" viewBox="0 0 24 24"><path d="M4 12h16m-6-6 6 6-6 6"/></symbol>
</svg>
    <div class="app-shell">
        <aside class="sidebar" id="sidebar">
            <a class="brand" href="{{ route('dashboard') }}">
                <span class="brand-symbol"><svg aria-hidden="true"><use href="#icon-monitor"/></svg></span>Gestão de Ativos
                <small>Estoque e descarte de equipamentos</small>
            </a>

            <nav aria-label="Menu principal">
                <a
                    class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                    href="{{ route('dashboard') }}"
                >
                    Início
                </a>

                <p class="nav-heading">Consultas</p>

                @can('viewAny', \App\Models\Asset::class)
                    <a
                        class="nav-link {{ request()->routeIs('assets.index', 'assets.show') ? 'active' : '' }}"
                        href="{{ route('assets.index') }}"
                    >
                        Equipamentos
                    </a>
                @endcan

                @can('viewAny', \App\Models\StockBalance::class)
                    <a
                        class="nav-link {{ request()->routeIs('stock-balances.*') ? 'active' : '' }}"
                        href="{{ route('stock-balances.index') }}"
                    >
                        Estoque por quantidade
                    </a>
                @endcan

                <p class="nav-heading">Movimentações</p>

                @can('create', \App\Models\Asset::class)
                    <a
                        class="nav-link {{ request()->routeIs('assets.batch.*') ? 'active' : '' }}"
                        href="{{ route('assets.batch.create') }}"
                    >
                        Entrada de equipamentos
                    </a>
                @endcan

                @can('recordEntry', \App\Models\StockBalance::class)
                    <a
                        class="nav-link {{ request()->routeIs('stock-entries.*') ? 'active' : '' }}"
                        href="{{ route('stock-entries.create') }}"
                    >
                        Entrada por quantidade
                    </a>
                @endcan

                @can('recordExit', \App\Models\Asset::class)
                    <a
                        class="nav-link {{ request()->routeIs('asset-exits.*') ? 'active' : '' }}"
                        href="{{ route('asset-exits.create') }}"
                    >
                        Saída de equipamentos
                    </a>
                @endcan

                @can('recordExit', \App\Models\StockBalance::class)
                    <a
                        class="nav-link {{ request()->routeIs('stock-exits.*') ? 'active' : '' }}"
                        href="{{ route('stock-exits.create') }}"
                    >
                        Saída por quantidade
                    </a>
                @endcan

                @can('recordReturn', \App\Models\Asset::class)
                    <a
                        class="nav-link {{ request()->routeIs('asset-returns.*') ? 'active' : '' }}"
                        href="{{ route('asset-returns.create') }}"
                    >
                        Devolução de equipamentos
                    </a>
                @endcan

                <p class="nav-heading">Reposições e descarte</p>

                @can('viewAny', \App\Models\AssetReplacementRequest::class)
                    <a
                        class="nav-link {{ request()->routeIs('asset-replacements.*') ? 'active' : '' }}"
                        href="{{ route('asset-replacements.index') }}"
                    >
                        Reposição de equipamentos
                    </a>
                @endcan

                @can('viewAny', \App\Models\StockReplacementRequest::class)
                    <a
                        class="nav-link {{ request()->routeIs('stock-replacements.*') ? 'active' : '' }}"
                        href="{{ route('stock-replacements.index') }}"
                    >
                        Reposição por quantidade
                    </a>
                @endcan

                @can('viewAny', \App\Models\DisposalContainer::class)
                    <a
                        class="nav-link {{ request()->routeIs('disposal-containers.*') ? 'active' : '' }}"
                        href="{{ route('disposal-containers.index') }}"
                    >
                        Caçambas de descarte
                    </a>
                @endcan

                <p class="nav-heading">Cadastros</p>

                @can('create', \App\Models\Item::class)
                    <a
                        class="nav-link {{ request()->routeIs('items.*') ? 'active' : '' }}"
                        href="{{ route('items.create') }}"
                    >
                        Cadastrar item
                    </a>
                @endcan

                @can('viewAny', \App\Models\Category::class)
                    <a
                        class="nav-link {{ request()->routeIs('categories.index') ? 'active' : '' }}"
                        href="{{ route('categories.index') }}"
                    >
                        Categorias
                    </a>
                @endcan

                @can('create', \App\Models\Category::class)
                    <a
                        class="nav-link {{ request()->routeIs('categories.create') ? 'active' : '' }}"
                        href="{{ route('categories.create') }}"
                    >
                        Cadastrar categoria
                    </a>
                @endcan
            </nav>

            <form
                class="logout-form"
                method="POST"
                action="{{ route('logout') }}"
            >
                @csrf
                <button class="logout-button" type="submit">
                    Sair do sistema
                </button>
            </form>
        </aside>

        <div class="main-area">
            <header class="topbar">
                <button
                    class="menu-toggle"
                    id="menu-toggle"
                    type="button"
                    aria-controls="sidebar"
                    aria-expanded="false"
                >
                    Menu
                </button>

                <span class="topbar-title">
                    @yield('title', 'Painel')
                </span>

                <div class="user-info">
                    {{ auth()->user()->name }}
                    <br>
                    {{ auth()->user()->role === 'admin'
                        ? 'Administrador'
                        : 'Operador' }}
                </div>
            </header>

            <main class="content" id="main-content">
                @if (session('status'))
                    <div class="alert alert-success" role="status">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-error" role="alert">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script>
        const menuToggle = document.getElementById('menu-toggle');
        const sidebar = document.getElementById('sidebar');

        menuToggle.addEventListener('click', () => {
            const open = sidebar.classList.toggle('is-open');
            menuToggle.setAttribute('aria-expanded', String(open));
        });
    </script>

    @stack('scripts')
</body>
</html>
