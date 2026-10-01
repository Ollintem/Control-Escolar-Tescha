<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>TESCHA | Panel del Alumno</title>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', '-apple-system', 'Segoe UI', 'sans-serif'] },
        colors: {
          brand: { 50: '#fce8ef', 100: '#f8edf1', 400: '#8c2448', 500: '#8b2346', 600: '#791e41', 700: '#6d1938', 800: '#56132d' },
          gold:  { 300: '#f5d891', 400: '#f4d894', 500: '#c96b00' },
          cream: '#f6f4f2',
          ink:   '#8c2448'
        },
        keyframes: {
          modalIn: { '0%': { opacity: 0, transform: 'translateY(8px) scale(.985)' }, '100%': { opacity: 1, transform: 'translateY(0) scale(1)' } },
          toastIn: { '0%': { opacity: 0, transform: 'translateY(10px)' }, '100%': { opacity: 1, transform: 'translateY(0)' } }
        },
        animation: {
          modalIn: 'modalIn .18s ease-out',
          toastIn: 'toastIn .2s ease-out'
        }
      }
    }
  }
</script>

<style>
  ::-webkit-scrollbar { width: 8px; height: 8px; }
  ::-webkit-scrollbar-thumb { background: #d9dde3; border-radius: 999px; }
  input[type="checkbox"] { accent-color: #8b2346; }
</style>
</head>

@php
    // Iniciales del alumno autenticado para los avatares del sidebar/header.
    $partesNombre = preg_split('/\s+/', trim(Auth::user()->name ?? 'Alumno')) ?: ['Alumno'];
    $iniciales = strtoupper(substr($partesNombre[0], 0, 1)
        . (isset($partesNombre[1]) ? substr($partesNombre[1], 0, 1) : ''));

    $rolNombre = Auth::user()->role?->nombre ?? 'Alumno';

    // Estado activo del enlace del sidebar según la ruta actual.
    $navClases = fn (bool $activo) => $activo
        ? 'bg-white/10 text-white border-gold-300'
        : 'text-white/70 border-transparent hover:text-white hover:bg-white/10 hover:translate-x-0.5';
@endphp

<body class="m-0 bg-cream text-ink font-sans">
  <div id="app-shell" class="grid grid-cols-1 md:grid-cols-[242px_minmax(0,1fr)] min-h-screen">

    <!-- overlay para sidebar móvil -->
    <div id="sidebar-overlay" class="hidden fixed inset-0 bg-black/40 z-40 md:hidden"></div>

    <aside id="sidebar"
      class="fixed md:static z-50 md:z-auto -translate-x-full md:translate-x-0 transition-transform duration-200 w-[242px] h-full md:h-auto flex flex-col px-3.5 pt-6 pb-4 text-white bg-gradient-to-b from-brand-800 to-brand-700">

      <div class="flex items-center gap-3 px-3 pb-6 border-b border-white/10">
        <div class="grid place-items-center w-[42px] h-[42px] rounded-[13px] text-gold-400 bg-white/10">
          <i data-lucide="graduation-cap" class="w-[23px]"></i>
        </div>
        <div>
          <b class="block text-[17px] tracking-tight">TESCHA</b>
          <span class="block mt-0.5 text-white/60 text-[10px] font-bold tracking-[.09em]">ALUMNO</span>
        </div>
        <button id="sidebar-close" class="ml-auto md:hidden text-white/70 hover:text-white">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>

      <nav class="grid gap-1 mt-3.5 overflow-y-auto">
        {{-- ===== ÚNICOS MÓDULOS DEL ALUMNO: sin módulos administrativos ===== --}}
        <p class="mx-3 mt-3.5 mb-1.5 text-white/45 text-[10px] font-extrabold tracking-[.12em]">PRINCIPAL</p>

        <a href="{{ route('panel.alumno.index') }}"
           class="nav-btn flex items-center w-full gap-2.5 px-3 py-2.5 rounded-xl border-l-[3px] font-semibold text-[13px] text-left transition-all duration-200 {{ $navClases(request()->routeIs('panel.alumno.index')) }}">
          <i data-lucide="layout-dashboard" class="w-[18px] h-[18px]"></i>
          <span>Inicio</span>
        </a>

        <p class="mx-3 mt-3.5 mb-1.5 text-white/45 text-[10px] font-extrabold tracking-[.12em]">MI INFORMACIÓN</p>

        <a href="{{ route('panel.alumno.calificaciones') }}"
           class="nav-btn flex items-center w-full gap-2.5 px-3 py-2.5 rounded-xl border-l-[3px] font-semibold text-[13px] text-left transition-all duration-200 {{ $navClases(request()->routeIs('panel.alumno.calificaciones')) }}">
          <i data-lucide="clipboard-list" class="w-[18px] h-[18px]"></i>
          <span>Mis Calificaciones</span>
        </a>

        <a href="{{ route('panel.alumno.historial') }}"
           class="nav-btn flex items-center w-full gap-2.5 px-3 py-2.5 rounded-xl border-l-[3px] font-semibold text-[13px] text-left transition-all duration-200 {{ $navClases(request()->routeIs('panel.alumno.historial')) }}">
          <i data-lucide="scroll-text" class="w-[18px] h-[18px]"></i>
          <span>Mi Historial Académico</span>
        </a>
      </nav>

      <div class="mt-auto pt-4 px-2.5 border-t border-white/10">
        <div class="flex items-center gap-2.5">
          <div class="grid place-items-center w-[34px] h-[34px] rounded-full text-white bg-gold-500 text-xs font-extrabold">{{ $iniciales }}</div>
          <div class="min-w-0">
            <b class="block text-xs truncate max-w-[145px]">{{ Auth::user()->name ?? 'Alumno' }}</b>
            <span class="block mt-0.5 text-white/55 text-[11px] truncate max-w-[145px]">{{ $rolNombre }} · TESCHA</span>
          </div>
        </div>

        <!-- Formulario POST para Cerrar Sesión (Sidebar) -->
        <form method="POST" action="{{ route('logout') }}" class="w-full">
          @csrf
          <button type="submit"
            class="mt-3 flex items-center justify-center gap-2 w-full py-2.5 rounded-xl border border-white/15 text-white/85 text-[12px] font-bold transition-all duration-200 hover:bg-red-500/20 hover:border-red-300/40 hover:text-white hover:-translate-y-0.5">
            <i data-lucide="log-out" class="w-4 h-4"></i>
            Cerrar sesión
          </button>
        </form>
      </div>
    </aside>

    <div class="min-w-0">
      <header class="flex items-center justify-between gap-4 h-[74px] px-5 md:px-7.5 border-b border-[#e9e5e2] bg-white sticky top-0 z-30">
        <div class="flex items-center gap-2.5">
          <button id="sidebar-open" class="md:hidden grid place-items-center w-9 h-9 rounded-lg text-[#677287] hover:bg-black/5">
            <i data-lucide="menu" class="w-5 h-5"></i>
          </button>
          <div class="flex items-center gap-2 text-[#8993a4] text-[13px]">
            <span class="hidden sm:inline">TESCHA</span>
            <i class="hidden sm:inline text-[#c1c6cf] not-italic">›</i>
            <b id="breadcrumb-title" class="text-brand-700">@yield('miga-de-pan', 'Panel del Alumno')</b>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <span class="hidden sm:inline-block px-2 py-1 rounded-md text-brand-700 bg-brand-100 text-[10px] font-extrabold tracking-[.05em]">ALUMNO</span>

          <div class="grid place-items-center w-[38px] h-[38px] rounded-full text-white bg-gold-500 text-xs font-extrabold">{{ $iniciales }}</div>

          <div class="hidden md:block">
            <b class="block text-[13px]">{{ Auth::user()->name ?? 'Alumno' }}</b>
            <span class="block mt-0.5 text-[#8a94a6] text-[11px]">{{ $rolNombre }}</span>
          </div>

          <!-- Formulario POST para Cerrar Sesión (Header) -->
          <form method="POST" action="{{ route('logout') }}" class="hidden md:block">
            @csrf
            <button type="submit" title="Cerrar sesión"
              class="grid place-items-center w-9 h-9 rounded-full text-[#677287] transition-all duration-200 hover:bg-brand-50 hover:text-brand-600 hover:scale-110">
              <i data-lucide="log-out" class="w-[18px] h-[18px]"></i>
            </button>
          </form>
        </div>
      </header>

      <main class="p-5 md:p-8">
        @yield('content')
      </main>
    </div>
  </div>

  <!-- contenedor de notificaciones toast -->
  <div id="toast-container" class="fixed bottom-5 right-5 z-[1200] flex flex-col gap-2"></div>

  <script>
    lucide.createIcons();

    /* ---------- Sidebar móvil ---------- */
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    function openSidebar() { sidebar.classList.remove('-translate-x-full'); sidebarOverlay.classList.remove('hidden'); }
    function closeSidebar() { sidebar.classList.add('-translate-x-full'); sidebarOverlay.classList.add('hidden'); }
    document.getElementById('sidebar-open').addEventListener('click', openSidebar);
    document.getElementById('sidebar-close').addEventListener('click', closeSidebar);
    sidebarOverlay.addEventListener('click', closeSidebar);

    /* ---------- Toasts ---------- */
    function showToast(message) {
      const container = document.getElementById('toast-container');
      const toast = document.createElement('div');
      toast.className = 'animate-toastIn flex items-center gap-2 py-3 px-4 rounded-xl bg-[#243044] text-white text-[13px] font-medium shadow-lg max-w-[280px]';
      toast.innerHTML = `<i data-lucide="info" class="w-4 h-4 text-gold-300 shrink-0"></i><span>${message}</span>`;
      container.appendChild(toast);
      lucide.createIcons();
      setTimeout(() => {
        toast.style.transition = 'opacity .2s, transform .2s';
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(6px)';
        setTimeout(() => toast.remove(), 200);
      }, 2600);
    }
    document.querySelectorAll('[data-toast]').forEach(el => {
      el.addEventListener('click', () => showToast(`${el.dataset.toast}`));
    });
  </script>
</body>
</html>
