<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>TESCHA | Panel de Administración General</title>

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
@livewireStyles
</head>

<body class="m-0 bg-cream text-ink font-sans">
  <div id="app-shell" class="grid grid-cols-1 md:grid-cols-[242px_minmax(0,1fr)] min-h-screen">

    <!-- overlay para sidebar móvil -->
    <div id="sidebar-overlay" class="hidden fixed inset-0 bg-black/40 z-40 md:hidden"></div>

    <aside id="sidebar"
      class="fixed md:static z-50 md:z-auto -translate-x-full md:translate-x-0 transition-transform duration-200 w-[242px] h-full md:h-auto flex flex-col px-3.5 pt-6 pb-4 text-white bg-gradient-to-b from-brand-800 to-brand-700">

      <div class="flex items-center gap-3 px-3 pb-6 border-b border-white/10">
        <div class="grid place-items-center w-[42px] h-[42px] rounded-[13px] text-gold-400 bg-white/10">
          <i data-lucide="shield-check" class="w-[23px]"></i>
        </div>
        <div>
          <b class="block text-[17px] tracking-tight">TESCHA</b>
          <span class="block mt-0.5 text-white/60 text-[10px] font-bold tracking-[.09em]">ADMINISTRACIÓN</span>
        </div>
        <button id="sidebar-close" class="ml-auto md:hidden text-white/70 hover:text-white">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>

      <nav class="grid gap-1 mt-3.5 overflow-y-auto">
        <p class="mx-3 mt-3.5 mb-1.5 text-white/45 text-[10px] font-extrabold tracking-[.12em]">PANEL DE CONTROL</p>

        <button data-nav id="btn-dashboard" class="nav-btn flex items-center w-full gap-2.5 px-3 py-2.5 rounded-xl text-white bg-white/10 border-l-[3px] border-gold-300 font-semibold text-[13px] text-left transition-all duration-200 hover:bg-white/15 hover:translate-x-0.5">
          <i data-lucide="layout-dashboard" class="w-[18px] h-[18px]"></i>
          <span>Dashboard Admin</span>
        </button>

        <p class="mx-3 mt-3.5 mb-1.5 text-white/45 text-[10px] font-extrabold tracking-[.12em]">ADMINISTRACIÓN SISTEMA</p>

        <button data-nav id="btn-roles" class="nav-btn flex items-center w-full gap-2.5 px-3 py-2.5 rounded-xl text-white/70 border-l-[3px] border-transparent font-semibold text-[13px] text-left transition-all duration-200 hover:text-white hover:bg-white/10 hover:translate-x-0.5">
          <i data-lucide="users" class="w-[18px] h-[18px]"></i>
          <span>Usuarios y Roles</span>
        </button>

        <button onclick="window.location.href='{{ url('/admin/permisos') }}'" class="nav-btn flex items-center w-full gap-2.5 px-3 py-2.5 rounded-xl text-white/70 border-l-[3px] border-transparent font-semibold text-[13px] text-left transition-all duration-200 hover:text-white hover:bg-white/10 hover:translate-x-0.5">
          <i data-lucide="shield-half" class="w-[18px] h-[18px]"></i>
          <span>Módulo Permisos</span>
        </button>

        <button data-nav id="btn-docentes" class="nav-btn flex items-center w-full gap-2.5 px-3 py-2.5 rounded-xl text-white/70 border-l-[3px] border-transparent font-semibold text-[13px] text-left transition-all duration-200 hover:text-white hover:bg-white/10 hover:translate-x-0.5">
          <i data-lucide="user-cog" class="w-[18px] h-[18px]"></i>
          <span>Docentes</span>
        </button>

        <button data-nav id="btn-jefes-carrera" class="nav-btn flex items-center w-full gap-2.5 px-3 py-2.5 rounded-xl text-white/70 border-l-[3px] border-transparent font-semibold text-[13px] text-left transition-all duration-200 hover:text-white hover:bg-white/10 hover:translate-x-0.5">
          <i data-lucide="award" class="w-[18px] h-[18px]"></i>
          <span>Jefes de Carrera</span>
        </button>

        <button data-toast="Control Escolar" class="nav-btn flex items-center w-full gap-2.5 px-3 py-2.5 rounded-xl text-white/70 border-l-[3px] border-transparent font-semibold text-[13px] text-left transition-all duration-200 hover:text-white hover:bg-white/10 hover:translate-x-0.5">
          <i data-lucide="graduation-cap" class="w-[18px] h-[18px]"></i>
          <span>Control Escolar</span>
        </button>

        <button data-toast="Configuración del sistema" class="nav-btn flex items-center w-full gap-2.5 px-3 py-2.5 rounded-xl text-white/70 border-l-[3px] border-transparent font-semibold text-[13px] text-left transition-all duration-200 hover:text-white hover:bg-white/10 hover:translate-x-0.5">
          <i data-lucide="settings" class="w-[18px] h-[18px]"></i>
          <span>Configuración Sistema</span>
        </button>

        <p class="mx-3 mt-3.5 mb-1.5 text-white/45 text-[10px] font-extrabold tracking-[.12em]">GESTIÓN ACADÉMICA</p>

        <button data-nav id="btn-carreras" class="nav-btn flex items-center w-full gap-2.5 px-3 py-2.5 rounded-xl text-white/70 border-l-[3px] border-transparent font-semibold text-[13px] text-left transition-all duration-200 hover:text-white hover:bg-white/10 hover:translate-x-0.5">
          <i data-lucide="landmark" class="w-[18px] h-[18px]"></i>
          <span>Carreras</span>
        </button>

        <button data-nav id="btn-semestres-periodos" class="nav-btn flex items-center w-full gap-2.5 px-3 py-2.5 rounded-xl text-white/70 border-l-[3px] border-transparent font-semibold text-[13px] text-left transition-all duration-200 hover:text-white hover:bg-white/10 hover:translate-x-0.5">
          <i data-lucide="calendar-days" class="w-[18px] h-[18px]"></i>
          <span>Semestres / Periodos</span>
        </button>

        <button data-nav id="btn-materias-plan" class="nav-btn flex items-center w-full gap-2.5 px-3 py-2.5 rounded-xl text-white/70 border-l-[3px] border-transparent font-semibold text-[13px] text-left transition-all duration-200 hover:text-white hover:bg-white/10 hover:translate-x-0.5">
          <i data-lucide="book-open" class="w-[18px] h-[18px]"></i>
          <span>Materias y Plan</span>
        </button>

        <button data-nav id="btn-alumnos" class="nav-btn flex items-center w-full gap-2.5 px-3 py-2.5 rounded-xl text-white/70 border-l-[3px] border-transparent font-semibold text-[13px] text-left transition-all duration-200 hover:text-white hover:bg-white/10 hover:translate-x-0.5">
          <i data-lucide="users-round" class="w-[18px] h-[18px]"></i>
          <span>Alumnos</span>
        </button>

        <button data-nav id="btn-grupos" class="nav-btn flex items-center w-full gap-2.5 px-3 py-2.5 rounded-xl text-white/70 border-l-[3px] border-transparent font-semibold text-[13px] text-left transition-all duration-200 hover:text-white hover:bg-white/10 hover:translate-x-0.5">
          <i data-lucide="grid-2x2" class="w-[18px] h-[18px]"></i>
          <span>Grupos</span>
        </button>

        <p class="mx-3 mt-3.5 mb-1.5 text-white/45 text-[10px] font-extrabold tracking-[.12em]">REPORTES Y AUDITORÍA</p>

        <button data-toast="Bitácora / Logs" class="nav-btn flex items-center w-full gap-2.5 px-3 py-2.5 rounded-xl text-white/70 border-l-[3px] border-transparent font-semibold text-[13px] text-left transition-all duration-200 hover:text-white hover:bg-white/10 hover:translate-x-0.5">
          <i data-lucide="history" class="w-[18px] h-[18px]"></i>
          <span>Bitácora / Logs</span>
        </button>

        <button data-toast="Reportes General" class="nav-btn flex items-center w-full gap-2.5 px-3 py-2.5 rounded-xl text-white/70 border-l-[3px] border-transparent font-semibold text-[13px] text-left transition-all duration-200 hover:text-white hover:bg-white/10 hover:translate-x-0.5">
          <i data-lucide="file-spreadsheet" class="w-[18px] h-[18px]"></i>
          <span>Reportes General</span>
        </button>
      </nav>

      <div class="mt-auto pt-4 px-2.5 border-t border-white/10">
        <div class="flex items-center gap-2.5">
          <div class="grid place-items-center w-[34px] h-[34px] rounded-full text-white bg-gold-500 text-xs font-extrabold">AD</div>
          <div class="min-w-0">
            <b class="block text-xs truncate max-w-[145px]">{{ Auth::user()->name ?? 'Administrador' }}</b>
            <span class="block mt-0.5 text-white/55 text-[11px] truncate max-w-[145px]">SuperAdmin · TESCHA</span>
          </div>
        </div>

        <!-- Formulario POST para Cerrar Sesión (Sidebar) -->
        <form method="POST" action="{{ route('logout') }}" class="w-full">
          @csrf
          <button type="submit" id="btn-logout"
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
            <b id="breadcrumb-title" class="text-brand-700">Administración General</b>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <span class="hidden sm:inline-block px-2 py-1 rounded-md text-brand-700 bg-brand-100 text-[10px] font-extrabold tracking-[.05em]">SUPERADMIN</span>

          <button data-toast="Notificaciones" class="relative grid place-items-center w-9 h-9 rounded-full text-[#677287] hover:bg-black/5 transition-all duration-200 hover:scale-110">
            <i data-lucide="bell" class="w-5 h-5"></i>
            <span class="absolute top-2 right-2 w-[7px] h-[7px] rounded-full bg-brand-500 ring-2 ring-white"></span>
          </button>

          <div class="grid place-items-center w-[38px] h-[38px] rounded-full text-white bg-gold-500 text-xs font-extrabold">AD</div>

          <div class="hidden md:block">
            <b class="block text-[13px]">{{ Auth::user()->name ?? 'Admin Sistema' }}</b>
            <span class="block mt-0.5 text-[#8a94a6] text-[11px]">Control Escolar</span>
          </div>

          <!-- Formulario POST para Cerrar Sesión (Header) -->
          <form method="POST" action="{{ route('logout') }}" class="hidden md:block">
            @csrf
            <button type="submit" id="btn-logout-header" title="Cerrar sesión"
              class="grid place-items-center w-9 h-9 rounded-full text-[#677287] transition-all duration-200 hover:bg-brand-50 hover:text-brand-600 hover:scale-110">
              <i data-lucide="log-out" class="w-[18px] h-[18px]"></i>
            </button>
          </form>
        </div>
      </header>

      <main class="p-5 md:p-8">

        <!-- VISTA DASHBOARD PRINCIPAL -->
        <div id="view-dashboard" class="view-panel">
          <h1 class="m-0 text-[#202b3d] text-[26px] md:text-[29px] tracking-tight">Panel de Administración General</h1>
          <p class="mt-1.5 mb-6 text-[#748095] text-sm">Bienvenido, {{ Auth::user()->name ?? 'Administrador del Sistema' }} · {{ \Carbon\Carbon::now()->isoFormat('D [de] MMMM [de] YYYY') }}</p>

          <section class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 p-5 rounded-[17px] text-white bg-gradient-to-r from-brand-800 to-brand-700 shadow-[0_12px_25px_rgba(92,19,48,.16)]">
            <div class="flex items-center gap-4">
              <div class="grid place-items-center w-12 h-12 rounded-[13px] text-gold-300 bg-white/15">
                <i data-lucide="shield-alert" class="w-[23px]"></i>
              </div>
              <div>
                <span class="text-white/65 text-[11px] font-extrabold tracking-[.08em]">ESTADO DEL SISTEMA</span>
                <strong class="block mt-1 text-lg md:text-xl tracking-tight">Servidores y Servicios Operativos</strong>
              </div>
            </div>

            <div class="hidden sm:flex items-center gap-3.5">
              <div class="min-w-[130px] py-2 px-3.5 rounded-xl bg-white/15 text-center">
                <b class="text-base">2026-2</b>
                <span class="block mt-0.5 text-white/65 text-[11px]">Periodo Activo</span>
              </div>
              <span class="flex items-center gap-1.5 py-2.5 px-3 rounded-full text-emerald-300 bg-emerald-400/20 text-xs font-extrabold">
                <i class="w-[7px] h-[7px] rounded-full bg-emerald-400 inline-block"></i>
                Sistema 100% Ok
              </span>
            </div>
          </section>

          <section id="stats" class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-5"></section>

          <section class="mt-5 p-5 rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
            <h2 class="m-0 text-[#293448] text-[17px]">Acciones rápidas de Administración</h2>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mt-4">
              <button data-toast="Crear Usuario" class="quick-action min-h-[124px] p-4 rounded-[13px] border border-[#f0eeec] bg-white text-[#253146] text-center transition-all duration-200 hover:border-[#ddc3cd] hover:bg-[#fffafa] hover:-translate-y-1 hover:shadow-lg">
                <i class="grid place-items-center w-[42px] h-[42px] mx-auto mb-3 rounded-xl text-brand-500 bg-brand-100" data-lucide="user-plus"></i>
                <b class="block text-[13px]">Crear Usuario</b>
                <span class="block mt-1 text-[#929cad] text-[11px]">Alta de Admin/Docente</span>
              </button>

              <button data-toast="Nuevo Periodo" class="quick-action min-h-[124px] p-4 rounded-[13px] border border-[#f0eeec] bg-white text-[#253146] text-center transition-all duration-200 hover:border-[#ddc3cd] hover:bg-[#fffafa] hover:-translate-y-1 hover:shadow-lg">
                <i class="grid place-items-center w-[42px] h-[42px] mx-auto mb-3 rounded-xl text-blue-600 bg-blue-50" data-lucide="calendar-plus"></i>
                <b class="block text-[13px]">Nuevo Periodo</b>
                <span class="block mt-1 text-[#929cad] text-[11px]">Configurar ciclo escolar</span>
              </button>

              <button onclick="showRolesView()" class="quick-action min-h-[124px] p-4 rounded-[13px] border border-[#f0eeec] bg-white text-[#253146] text-center transition-all duration-200 hover:border-[#ddc3cd] hover:bg-[#fffafa] hover:-translate-y-1 hover:shadow-lg">
                <i class="grid place-items-center w-[42px] h-[42px] mx-auto mb-3 rounded-xl text-gold-500 bg-amber-50" data-lucide="key-round"></i>
                <b class="block text-[13px]">Gestión de Roles</b>
                <span class="block mt-1 text-[#929cad] text-[11px]">Permisos de usuarios</span>
              </button>

              <button data-toast="Respaldos / Logs" class="quick-action min-h-[124px] p-4 rounded-[13px] border border-[#f0eeec] bg-white text-[#253146] text-center transition-all duration-200 hover:border-[#ddc3cd] hover:bg-[#fffafa] hover:-translate-y-1 hover:shadow-lg">
                <i class="grid place-items-center w-[42px] h-[42px] mx-auto mb-3 rounded-xl text-emerald-600 bg-emerald-50" data-lucide="database-backup"></i>
                <b class="block text-[13px]">Respaldos / Logs</b>
                <span class="block mt-1 text-[#929cad] text-[11px]">Ver bitácora de sistema</span>
              </button>
            </div>
          </section>

          <section class="grid grid-cols-1 lg:grid-cols-[1.45fr_.95fr] gap-5 mt-5">
            <article class="overflow-hidden rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
              <div class="flex items-center justify-between px-5 py-5 border-b border-[#f1efed]">
                <h2 class="m-0 text-[#293448] text-base">Bitácora del Sistema (Actividad General)</h2>
              </div>

              <div class="grid grid-cols-[38px_1fr] sm:grid-cols-[42px_1fr_auto] gap-3 items-center px-5 py-4 border-b border-cream">
                <div class="grid place-items-center w-[38px] h-[38px] rounded-[10px] text-brand-500 bg-brand-100"><i data-lucide="key" class="w-[18px]"></i></div>
                <div>
                  <b class="block text-[#283449] text-[13px]">Cambio de permisos — Docente Ramírez</b>
                  <span class="block mt-0.5 text-[#929cad] text-[11px]">Asignación de rol de captura de actas</span>
                </div>
                <div class="hidden sm:block text-[#98a2b2] text-[11px] text-right whitespace-nowrap">Hoy 09:30<br>SuperAdmin</div>
              </div>

              <div class="grid grid-cols-[38px_1fr] sm:grid-cols-[42px_1fr_auto] gap-3 items-center px-5 py-4 border-b border-cream">
                <div class="grid place-items-center w-[38px] h-[38px] rounded-[10px] text-gold-500 bg-amber-50"><i data-lucide="database" class="w-[18px]"></i></div>
                <div>
                  <b class="block text-[#283449] text-[13px]">Cierre de Actas Parcial 3 — ISC</b>
                  <span class="block mt-0.5 text-[#929cad] text-[11px]">Proceso del sistema automatizado</span>
                </div>
                <div class="hidden sm:block text-[#98a2b2] text-[11px] text-right whitespace-nowrap">Hoy 08:50<br>Sistema</div>
              </div>

              <div class="grid grid-cols-[38px_1fr] sm:grid-cols-[42px_1fr_auto] gap-3 items-center px-5 py-4 border-b border-cream">
                <div class="grid place-items-center w-[38px] h-[38px] rounded-[10px] text-blue-600 bg-blue-50"><i data-lucide="user-check" class="w-[18px]"></i></div>
                <div>
                  <b class="block text-[#283449] text-[13px]">Usuario Docente Registrado — Ing. Carlos Mendoza</b>
                  <span class="block mt-0.5 text-[#929cad] text-[11px]">Departamento de Sistemas</span>
                </div>
                <div class="hidden sm:block text-[#98a2b2] text-[11px] text-right whitespace-nowrap">Ayer 16:22<br>SuperAdmin</div>
              </div>

              <div class="grid grid-cols-[38px_1fr] sm:grid-cols-[42px_1fr_auto] gap-3 items-center px-5 py-4">
                <div class="grid place-items-center w-[38px] h-[38px] rounded-[10px] text-emerald-600 bg-emerald-50"><i data-lucide="settings-2" class="w-[18px]"></i></div>
                <div>
                  <b class="block text-[#283449] text-[13px]">Apertura de Periodo Escolar — 2026-2</b>
                  <span class="block mt-0.5 text-[#929cad] text-[11px]">Parámetros globales actualizados</span>
                </div>
                <div class="hidden sm:block text-[#98a2b2] text-[11px] text-right whitespace-nowrap">Ayer 11:30<br>SuperAdmin</div>
              </div>
            </article>

            <article class="overflow-hidden rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
              <div class="flex items-center justify-between px-5 py-5 border-b border-[#f1efed]">
                <h2 class="m-0 text-[#293448] text-base">Carreras Activas</h2>
                <a href="#" data-toast="Gestión de carreras" class="text-brand-600 text-xs font-extrabold no-underline hover:text-brand-800">Gestionar →</a>
              </div>

              <div class="career-item px-5 py-4 border-b border-cream cursor-pointer transition-colors duration-150 hover:bg-[#fcfafb]" data-toast="Ingeniería Electromecánica">
                <div class="flex items-center gap-2">
                  <span class="px-1.5 py-1 rounded text-white bg-brand-500 text-[10px] font-extrabold">IM</span>
                  <span class="flex-1 min-w-0 truncate text-[#354156] text-xs font-bold">Ingeniería Electromecánica</span>
                  <span class="text-brand-500 text-base font-extrabold">›</span>
                </div>
                <div class="flex justify-between mt-2 text-[#98a2b2] text-[10px]">
                  <span>Configuración de retícula</span><span>Editar</span>
                </div>
              </div>

              <div class="career-item px-5 py-4 border-b border-cream cursor-pointer transition-colors duration-150 hover:bg-[#fcfafb]" data-toast="Ingeniería Electrónica">
                <div class="flex items-center gap-2">
                  <span class="px-1.5 py-1 rounded text-white bg-brand-500 text-[10px] font-extrabold">IE</span>
                  <span class="flex-1 min-w-0 truncate text-[#354156] text-xs font-bold">Ingeniería Electrónica</span>
                  <span class="text-brand-500 text-base font-extrabold">›</span>
                </div>
                <div class="flex justify-between mt-2 text-[#98a2b2] text-[10px]">
                  <span>Configuración de retícula</span><span>Editar</span>
                </div>
              </div>

              <div class="career-item px-5 py-4 border-b border-cream cursor-pointer transition-colors duration-150 hover:bg-[#fcfafb]" data-toast="Ingeniería Industrial">
                <div class="flex items-center gap-2">
                  <span class="px-1.5 py-1 rounded text-white bg-brand-500 text-[10px] font-extrabold">II</span>
                  <span class="flex-1 min-w-0 truncate text-[#354156] text-xs font-bold">Ingeniería Industrial</span>
                  <span class="text-brand-500 text-base font-extrabold">›</span>
                </div>
                <div class="flex justify-between mt-2 text-[#98a2b2] text-[10px]">
                  <span>Configuración de retícula</span><span>Editar</span>
                </div>
              </div>

              <div class="career-item px-5 py-4 border-b border-cream cursor-pointer transition-colors duration-150 hover:bg-[#fcfafb]" data-toast="Ingeniería Informática">
                <div class="flex items-center gap-2">
                  <span class="px-1.5 py-1 rounded text-white bg-brand-500 text-[10px] font-extrabold">IINF</span>
                  <span class="flex-1 min-w-0 truncate text-[#354156] text-xs font-bold">Ingeniería Informática</span>
                  <span class="text-brand-500 text-base font-extrabold">›</span>
                </div>
                <div class="flex justify-between mt-2 text-[#98a2b2] text-[10px]">
                  <span>Configuración de retícula</span><span>Editar</span>
                </div>
              </div>

              <div class="career-item px-5 py-4 border-b border-cream cursor-pointer transition-colors duration-150 hover:bg-[#fcfafb]" data-toast="Ingeniería en Sistemas Computacionales">
                <div class="flex items-center gap-2">
                  <span class="px-1.5 py-1 rounded text-white bg-brand-500 text-[10px] font-extrabold">ISC</span>
                  <span class="flex-1 min-w-0 truncate text-[#354156] text-xs font-bold">Ingeniería en Sistemas Computacionales</span>
                  <span class="text-brand-500 text-base font-extrabold">›</span>
                </div>
                <div class="flex justify-between mt-2 text-[#98a2b2] text-[10px]">
                  <span>Configuración de retícula</span><span>Editar</span>
                </div>
              </div>

              <div class="career-item px-5 py-4 cursor-pointer transition-colors duration-150 hover:bg-[#fcfafb]" data-toast="Ingeniería en Administración">
                <div class="flex items-center gap-2">
                  <span class="px-1.5 py-1 rounded text-white bg-brand-500 text-[10px] font-extrabold">IA</span>
                  <span class="flex-1 min-w-0 truncate text-[#354156] text-xs font-bold">Ingeniería en Administración</span>
                  <span class="text-brand-500 text-base font-extrabold">›</span>
                </div>
                <div class="flex justify-between mt-2 text-[#98a2b2] text-[10px]">
                  <span>Configuración de retícula</span><span>Editar</span>
                </div>
              </div>

              <div class="flex justify-between px-5 py-4 text-[#748095] bg-[#fafafa] text-xs">
                <span>Estatus global</span>
                <b class="text-[#2a3548]">6 Carreras en Regla</b>
              </div>
            </article>
          </section>
        </div>

        <!-- VISTA GESTIÓN DE USUARIOS Y ROLES (Livewire) -->
        <div id="view-roles" class="view-panel hidden">
          @livewire('gestion-roles')
        </div>

        <!-- VISTA MATRIZ DE PERMISOS (Livewire) -->
        <div id="view-permisos" class="view-panel hidden">
          @livewire('matriz-permisos-dashboard')
        </div>

        <!-- VISTA GESTIÓN DE DOCENTES (Livewire) -->
        <div id="view-docentes" class="view-panel hidden">
          @livewire('gestion-docentes')
        </div>

        <!-- VISTA GESTIÓN DE JEFES DE CARRERA (Livewire) -->
        <div id="view-jefes-carrera" class="view-panel hidden">
          @livewire('gestion-jefes-carrera')
        </div>

        <!-- VISTA GESTIÓN DE CARRERAS (Livewire) -->
        <div id="view-carreras" class="view-panel hidden">
          @livewire('gestion-carreras')
        </div>

        <!-- VISTA GESTIÓN DE SEMESTRES / PERIODOS (Livewire) -->
        <div id="view-semestres-periodos" class="view-panel hidden">
          @livewire('gestion-semestres-periodos')
        </div>

        <!-- VISTA GESTIÓN DE MATERIAS Y PLAN (Livewire) -->
        <div id="view-materias-plan" class="view-panel hidden">
          @livewire('gestion-materias-plan')
        </div>

        <!-- VISTA GESTIÓN DE ALUMNOS (Livewire) -->
        <div id="view-alumnos" class="view-panel hidden">
          @livewire('gestion-alumnos')
        </div>

        <!-- VISTA GESTIÓN DE GRUPOS (Livewire) -->
        <div id="view-grupos" class="view-panel hidden">
          @livewire('gestion-grupos')
        </div>


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
      el.addEventListener('click', () => showToast(`“${el.dataset.toast}” — módulo en desarrollo.`));
    });

    /* ---------- Vistas / navegación ---------- */
    const viewDashboard = document.getElementById('view-dashboard');
    const viewRoles = document.getElementById('view-roles');
    const viewPermisos = document.getElementById('view-permisos');
    const viewDocentes = document.getElementById('view-docentes');
    const viewJefesCarrera = document.getElementById('view-jefes-carrera');
    const viewCarreras = document.getElementById('view-carreras');
    const viewSemestresPeriodos = document.getElementById('view-semestres-periodos');
    const viewMateriasPlan = document.getElementById('view-materias-plan');
    const viewAlumnos = document.getElementById('view-alumnos');
    const viewGrupos = document.getElementById('view-grupos');
    const breadcrumbTitle = document.getElementById('breadcrumb-title');

    const btnDashboard = document.getElementById('btn-dashboard');
    const btnRoles = document.getElementById('btn-roles');
    const btnDocentes = document.getElementById('btn-docentes');
    const btnJefesCarrera = document.getElementById('btn-jefes-carrera');
    const btnCarreras = document.getElementById('btn-carreras');
    const btnSemestresPeriodos = document.getElementById('btn-semestres-periodos');
    const btnMateriasPlan = document.getElementById('btn-materias-plan');
    const btnAlumnos = document.getElementById('btn-alumnos');
    const btnGrupos = document.getElementById('btn-grupos');

    const navActiveClasses = ['bg-white/10', 'text-white', 'border-gold-300'];
    const navInactiveClasses = ['text-white/70', 'border-transparent'];

    function setActiveButton(element) {
      document.querySelectorAll('[data-nav]').forEach(item => {
        item.classList.remove(...navActiveClasses);
        item.classList.add(...navInactiveClasses);
      });
      if (element) {
        element.classList.remove(...navInactiveClasses);
        element.classList.add(...navActiveClasses);
      }
    }

    function hideAllViews() {
      [viewDashboard, viewRoles, viewPermisos, viewDocentes, viewJefesCarrera, viewCarreras, viewSemestresPeriodos, viewMateriasPlan, viewAlumnos, viewGrupos].forEach(view => view.classList.add('hidden'));
    }

    function showDashboardView() {
      hideAllViews();
      viewDashboard.classList.remove('hidden');
      breadcrumbTitle.textContent = 'Administración General';
      setActiveButton(btnDashboard);
      closeSidebar();
    }

    function showRolesView() {
      hideAllViews();
      viewRoles.classList.remove('hidden');
      breadcrumbTitle.textContent = 'Usuarios y Roles';
      setActiveButton(btnRoles);
      closeSidebar();
    }

    function showPermisosView() {
      hideAllViews();
      viewPermisos.classList.remove('hidden');
      breadcrumbTitle.textContent = 'Usuarios y Roles › Permisos';
      closeSidebar();
    }

    function showDocentesView() {
      hideAllViews();
      viewDocentes.classList.remove('hidden');
      breadcrumbTitle.textContent = 'Gestión de Docentes';
      setActiveButton(btnDocentes);
      closeSidebar();
    }

    function showJefesCarreraView() {
      hideAllViews();
      viewJefesCarrera.classList.remove('hidden');
      breadcrumbTitle.textContent = 'Gestión de Jefes de Carrera';
      setActiveButton(btnJefesCarrera);
      closeSidebar();
    }

    function showCarrerasView() {
      hideAllViews();
      viewCarreras.classList.remove('hidden');
      breadcrumbTitle.textContent = 'Gestión de Carreras';
      setActiveButton(btnCarreras);
      closeSidebar();
    }

    function showSemestresPeriodosView() {
      hideAllViews();
      viewSemestresPeriodos.classList.remove('hidden');
      breadcrumbTitle.textContent = 'Gestión de Semestres / Periodos';
      setActiveButton(btnSemestresPeriodos);
      closeSidebar();
    }

    function showMateriasPlanView() {
      hideAllViews();
      viewMateriasPlan.classList.remove('hidden');
      breadcrumbTitle.textContent = 'Gestión de Materias y Plan';
      setActiveButton(btnMateriasPlan);
      closeSidebar();
    }

    function showAlumnosView() {
      hideAllViews();
      viewAlumnos.classList.remove('hidden');
      breadcrumbTitle.textContent = 'Gestión de Alumnos';
      setActiveButton(btnAlumnos);
      closeSidebar();
    }

    function showGruposView() {
      hideAllViews();
      viewGrupos.classList.remove('hidden');
      breadcrumbTitle.textContent = 'Gestión de Grupos';
      setActiveButton(btnGrupos);
      closeSidebar();
    }

    /* ---------- Eventos ---------- */
    btnDashboard.addEventListener('click', showDashboardView);
    btnRoles.addEventListener('click', showRolesView);
    btnDocentes.addEventListener('click', showDocentesView);
    btnJefesCarrera.addEventListener('click', showJefesCarreraView);
    btnCarreras.addEventListener('click', showCarrerasView);
    btnSemestresPeriodos.addEventListener('click', showSemestresPeriodosView);
    btnMateriasPlan.addEventListener('click', showMateriasPlanView);
    btnAlumnos.addEventListener('click', showAlumnosView);
    btnGrupos.addEventListener('click', showGruposView);
  </script>
  @livewireScripts
</body>
</html>