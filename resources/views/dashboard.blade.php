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
          ink:   '#243044'
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

        <button data-nav id="btn-permisos" class="nav-btn flex items-center w-full gap-2.5 px-3 py-2.5 rounded-xl text-white/70 border-l-[3px] border-transparent font-semibold text-[13px] text-left transition-all duration-200 hover:text-white hover:bg-white/10 hover:translate-x-0.5">
          <i data-lucide="shield-half" class="w-[18px] h-[18px]"></i>
          <span>Módulo Permisos</span>
        </button>

        <button data-toast="Docentes" class="nav-btn flex items-center w-full gap-2.5 px-3 py-2.5 rounded-xl text-white/70 border-l-[3px] border-transparent font-semibold text-[13px] text-left transition-all duration-200 hover:text-white hover:bg-white/10 hover:translate-x-0.5">
          <i data-lucide="user-cog" class="w-[18px] h-[18px]"></i>
          <span>Docentes</span>
        </button>

        <button data-toast="Jefes de Carrera" class="nav-btn flex items-center w-full gap-2.5 px-3 py-2.5 rounded-xl text-white/70 border-l-[3px] border-transparent font-semibold text-[13px] text-left transition-all duration-200 hover:text-white hover:bg-white/10 hover:translate-x-0.5">
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

        <button data-toast="Carreras" class="nav-btn flex items-center w-full gap-2.5 px-3 py-2.5 rounded-xl text-white/70 border-l-[3px] border-transparent font-semibold text-[13px] text-left transition-all duration-200 hover:text-white hover:bg-white/10 hover:translate-x-0.5">
          <i data-lucide="landmark" class="w-[18px] h-[18px]"></i>
          <span>Carreras</span>
        </button>

        <button data-toast="Semestres / Periodos" class="nav-btn flex items-center w-full gap-2.5 px-3 py-2.5 rounded-xl text-white/70 border-l-[3px] border-transparent font-semibold text-[13px] text-left transition-all duration-200 hover:text-white hover:bg-white/10 hover:translate-x-0.5">
          <i data-lucide="calendar-days" class="w-[18px] h-[18px]"></i>
          <span>Semestres / Periodos</span>
        </button>

        <button data-toast="Materias y Plan" class="nav-btn flex items-center w-full gap-2.5 px-3 py-2.5 rounded-xl text-white/70 border-l-[3px] border-transparent font-semibold text-[13px] text-left transition-all duration-200 hover:text-white hover:bg-white/10 hover:translate-x-0.5">
          <i data-lucide="book-open" class="w-[18px] h-[18px]"></i>
          <span>Materias y Plan</span>
        </button>

        <button data-toast="Alumnos" class="nav-btn flex items-center w-full gap-2.5 px-3 py-2.5 rounded-xl text-white/70 border-l-[3px] border-transparent font-semibold text-[13px] text-left transition-all duration-200 hover:text-white hover:bg-white/10 hover:translate-x-0.5">
          <i data-lucide="users-round" class="w-[18px] h-[18px]"></i>
          <span>Alumnos</span>
        </button>

        <button data-toast="Grupos" class="nav-btn flex items-center w-full gap-2.5 px-3 py-2.5 rounded-xl text-white/70 border-l-[3px] border-transparent font-semibold text-[13px] text-left transition-all duration-200 hover:text-white hover:bg-white/10 hover:translate-x-0.5">
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

        <!-- VISTA GESTIÓN DE USUARIOS Y ROLES -->
        <div id="view-roles" class="view-panel hidden">
          <h1 class="m-0 text-[#202b3d] text-[26px] md:text-[29px] tracking-tight">Gestión de usuarios y roles</h1>
          <p class="mt-1.5 mb-6 text-[#748095] text-sm">Administra los perfiles del sistema y configura los permisos de acceso para cada rol.</p>

          <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 p-4 rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_8px_22px_rgba(31,38,50,.045)]">
            <div class="flex items-center gap-2.5 flex-1 sm:max-w-[430px] px-3.5 py-2.5 rounded-[11px] border border-[#e8e4e1] bg-[#faf9f8] text-[#8b95a5]">
              <i data-lucide="search" class="w-[17px]"></i>
              <input id="role-search" type="search" placeholder="Buscar rol por nombre o descripción..." class="w-full border-0 outline-none bg-transparent text-[#253146] text-[13px] font-medium">
            </div>
            <button type="button" onclick="openRoleModal('new')"
              class="inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-[11px] text-white bg-gradient-to-br from-brand-500 to-brand-700 shadow-[0_7px_16px_rgba(109,25,56,.18)] text-[13px] font-bold transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_10px_20px_rgba(109,25,56,.23)]">
              <i data-lucide="plus" class="w-4 h-4"></i>
              Nuevo rol
            </button>
          </div>

          <section class="mt-4 overflow-hidden rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_8px_22px_rgba(31,38,50,.045)]">
            <div class="flex items-center justify-between px-5 py-5 border-b border-[#efedeb]">
              <div>
                <h2 class="m-0 text-[#293448] text-base">Catálogo de roles</h2>
                <span class="text-[#919aaa] text-[11px]">Perfiles disponibles dentro del sistema TESCHA</span>
              </div>
              <span id="role-count" class="text-[#919aaa] text-[11px]">5 roles registrados</span>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full border-collapse min-w-[760px]">
                <thead>
                  <tr>
                    <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Rol / Perfil</th>
                    <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Usuarios</th>
                    <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Nivel de acceso</th>
                    <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Estatus</th>
                    <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Acciones</th>
                  </tr>
                </thead>
                <tbody id="roles-table-body"></tbody>
              </table>
              <div id="empty-role" class="hidden p-9 text-center text-[#8993a4] text-[13px]">No se encontraron roles con esa búsqueda.</div>
            </div>
          </section>
        </div>

        <!-- VISTA MATRIZ DE PERMISOS -->
        <div id="view-permisos" class="view-panel hidden">
          <h1 class="m-0 text-[#202b3d] text-[26px] md:text-[29px] tracking-tight">Matriz de permisos</h1>
          <p class="mt-1.5 mb-6 text-[#748095] text-sm">Marca o desmarca los permisos de cada rol por módulo del sistema TESCHA.</p>

          <div class="rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_8px_22px_rgba(31,38,50,.045)] overflow-hidden">
            <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-[#efedeb]">
              <div class="flex items-center gap-2.5">
                <div class="grid place-items-center w-9 h-9 rounded-[10px] text-brand-500 bg-brand-100"><i data-lucide="grid-3x3" class="w-[18px] h-[18px]"></i></div>
                <div>
                  <h2 class="m-0 text-[#293448] text-[15px]">Módulos y roles</h2>
                  <span class="text-[#919aaa] text-[11px]">Filas: módulos del sistema · Columnas: roles</span>
                </div>
              </div>
              <span class="hidden sm:flex items-center gap-1.5 text-[#9aa3b1] text-[10px] font-bold"><i data-lucide="lock" class="w-3 h-3"></i>Administrador con acceso fijo</span>
            </div>

            <div class="overflow-x-auto">
              <table id="permission-matrix" class="w-full border-collapse text-sm min-w-[640px]">
                <thead id="permission-matrix-head"></thead>
                <tbody id="permission-matrix-body"></tbody>
              </table>
            </div>
          </div>

          <div class="flex items-center justify-end gap-2.5 mt-4 py-4 px-5 rounded-2xl border border-[#ece9e6] bg-white">
            <button type="button" onclick="showRolesView()" class="py-2.5 px-4 rounded-[10px] border border-[#e5e1de] text-[#697487] bg-white font-bold text-xs transition-colors hover:bg-[#faf9f8]">Volver al catálogo</button>
            <button type="button" onclick="savePermissions()"
              class="inline-flex items-center gap-2 py-2.5 px-4 rounded-[11px] text-white bg-gradient-to-br from-brand-500 to-brand-700 shadow-[0_7px_16px_rgba(109,25,56,.18)] font-bold text-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_10px_20px_rgba(109,25,56,.23)]">
              <i data-lucide="save" class="w-4 h-4"></i>
              Guardar cambios
            </button>
          </div>
        </div>

        <!-- MODAL CREAR / EDITAR ROL -->
        <div id="role-modal" class="modal-backdrop fixed inset-0 z-[1000] hidden place-items-center p-5 bg-black/55 backdrop-blur-sm">
          <div class="w-full max-w-[520px] max-h-[calc(100vh-40px)] overflow-auto rounded-[20px] bg-white shadow-2xl animate-modalIn">
            <div class="flex items-start justify-between gap-4 px-5.5 pt-5.5 pb-4 border-b border-[#eeeae8]">
              <div>
                <h2 id="role-modal-title" class="m-0 text-[#283348] text-lg">Nuevo rol</h2>
                <p id="role-modal-subtitle" class="mt-1 mb-0 text-[#919aaa] text-[11px]">Registra un nuevo perfil de acceso para TESCHA.</p>
              </div>
              <button type="button" onclick="closeRoleModal()" aria-label="Cerrar"
                class="grid place-items-center w-[34px] h-[34px] rounded-[9px] text-[#788395] bg-[#f7f5f3] transition-colors hover:text-brand-700 hover:bg-brand-100">
                <i data-lucide="x" class="w-4 h-4"></i>
              </button>
            </div>
            <div class="p-5.5">
              <div class="mb-3.5">
                <label for="role-name-input" class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Nombre del rol</label>
                <input id="role-name-input" type="text" placeholder="Ej. Coordinador Académico" class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
              </div>
              <div class="mb-3.5">
                <label for="role-desc-input" class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Descripción</label>
                <textarea id="role-desc-input" placeholder="Describe brevemente las funciones de este perfil..." class="w-full min-h-[86px] py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] resize-y focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10"></textarea>
              </div>
              <div class="mb-3.5">
                <label for="role-level-input" class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Nivel de acceso</label>
                <input id="role-level-input" type="text" placeholder="Ej. Gestión académica" class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
              </div>
              <div id="edit-permissions-box" class="hidden items-center justify-between gap-4 mt-4 p-3.5 rounded-xl border border-[#eadfe3] bg-[#fcf7f9]">
                <div>
                  <strong class="block text-[#5c2036] text-xs">Permisos del rol</strong>
                  <span class="block mt-1 text-[#9a7d88] text-[10px]">Configura qué módulos puede consultar o administrar.</span>
                </div>
                <button type="button" onclick="openPermissionsFromModal()" class="py-2 px-3 rounded-[9px] border border-[#d7b5c2] text-brand-700 bg-white font-bold text-[11px] whitespace-nowrap transition-colors hover:bg-brand-100">Configurar permisos</button>
              </div>
            </div>
            <div class="flex justify-end gap-2 px-5.5 pb-5 pt-3.5">
              <button type="button" onclick="closeRoleModal()" class="py-2.5 px-4 rounded-[10px] border border-[#e5e1de] text-[#697487] bg-white font-bold text-xs transition-colors hover:bg-[#faf9f8]">Cancelar</button>
              <button type="button" onclick="saveRole()"
                class="inline-flex items-center gap-2 py-2.5 px-4 rounded-[11px] text-white bg-gradient-to-br from-brand-500 to-brand-700 shadow-[0_7px_16px_rgba(109,25,56,.18)] font-bold text-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_10px_20px_rgba(109,25,56,.23)]">
                <i data-lucide="check" class="w-4 h-4"></i>
                <span id="role-submit-text">Crear rol</span>
              </button>
            </div>
          </div>
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
    const breadcrumbTitle = document.getElementById('breadcrumb-title');

    const btnDashboard = document.getElementById('btn-dashboard');
    const btnRoles = document.getElementById('btn-roles');
    const btnPermisos = document.getElementById('btn-permisos');

    const ADMIN_ROLE_ID = 1;

    // ===== DATOS CARGADOS DESDE LA BASE DE DATOS =====
    let roles = [];
    let permisosFromDB = [];
    let permissionGroups = [];

    // Iconos por defecto según nombre del rol
    const roleIcons = {
      'administrador': 'shield-check',
      'control escolar': 'graduation-cap',
      'jefe de carrera': 'award',
      'docente': 'user-cog',
      'alumno': 'graduation-cap',
    };

    // Niveles de acceso por defecto
    const roleLevels = {
      'administrador': 'Acceso total',
      'control escolar': 'Administrativo',
      'jefe de carrera': 'Académico',
      'docente': 'Académico',
      'alumno': 'Consulta',
    };

    /**
     * Cargar roles desde la BD.
     */
    async function loadRolesFromDB() {
      try {
        const response = await fetch('{{ route("api.roles.index") }}', {
          headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!response.ok) throw new Error('Error al cargar roles');
        const data = await response.json();

        roles = data.map(r => ({
          id: r.id_rol,
          name: r.nombre,
          desc: r.descripcion || 'Perfil de acceso del sistema',
          users: r.users_count || 0,
          level: roleLevels[r.nombre.toLowerCase()] || 'Personalizado',
          icon: roleIcons[r.nombre.toLowerCase()] || 'shield',
          permissions: [],
          activo: r.activo,
          permisos_count: r.permisos_count || 0,
        }));

        // Después de cargar roles, cargar permisos para completar la matriz
        await loadPermisosFromDB();

        renderRoles(document.getElementById('role-search').value);
        renderStats();
      } catch (err) {
        console.error('loadRolesFromDB:', err);
        showToast('Error al cargar roles desde la base de datos.');
      }
    }

    /**
     * Cargar permisos desde la BD y construir permissionGroups.
     */
    async function loadPermisosFromDB() {
      try {
        const response = await fetch('{{ route("api.permisos.index") }}', {
          headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!response.ok) throw new Error('Error al cargar permisos');
        const data = await response.json();

        permisosFromDB = data.permisos;
        const matriz = data.matriz;

        // Agrupar permisos por módulo
        const agrupados = {};
        permisosFromDB.forEach(p => {
          if (!agrupados[p.modulo]) agrupados[p.modulo] = [];
          agrupados[p.modulo].push(p);
        });

        const iconMap = {
          'Usuarios': 'users',
          'Roles': 'shield-half',
          'Académico': 'book-open',
          'Calificaciones': 'search-check',
          'Reportes': 'file-text',
          'Configuración': 'settings-2',
          'General': 'grid-3x3',
        };

        permissionGroups = Object.entries(agrupados).map(([modulo, permisosList]) => ({
          title: modulo,
          icon: iconMap[modulo] || 'layers',
          items: permisosList.map(p => [
            p.descripcion || `${p.accion} ${p.modulo}`,
            p.accion,
            p.id_permiso,
          ]),
        }));

        // Actualizar permisos de cada rol según la matriz
        roles.forEach(role => {
          if (matriz[role.id]) {
            role.permissions = Object.entries(matriz[role.id])
              .filter(([_, val]) => val === true)
              .map(([key]) => Number(key));
          }
        });

      } catch (err) {
        console.error('loadPermisosFromDB:', err);
      }
    }

    let editingRoleId = null;

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
      [viewDashboard, viewRoles, viewPermisos].forEach(view => view.classList.add('hidden'));
    }

    function showDashboardView() {
      hideAllViews();
      viewDashboard.classList.remove('hidden');
      breadcrumbTitle.textContent = 'Administración General';
      setActiveButton(btnDashboard);
      renderStats();
      closeSidebar();
    }

    function showRolesView() {
      hideAllViews();
      viewRoles.classList.remove('hidden');
      breadcrumbTitle.textContent = 'Usuarios y Roles';
      setActiveButton(btnRoles);
      renderRoles();
      closeSidebar();
    }

    function showPermisosView(highlightRoleId = null) {
      hideAllViews();
      viewPermisos.classList.remove('hidden');
      breadcrumbTitle.textContent = 'Usuarios y Roles › Permisos';
      setActiveButton(btnPermisos);
      renderPermissionMatrix();
      closeSidebar();
      if (highlightRoleId) flashRoleColumn(Number(highlightRoleId));
    }

    function flashRoleColumn(roleId) {
      document.querySelectorAll(`[data-role-col="${roleId}"]`).forEach(cell => {
        cell.classList.add('bg-brand-50');
        setTimeout(() => cell.classList.remove('bg-brand-50'), 1200);
      });
    }

    function renderStats() {
      const totalModules = permissionGroups.reduce((sum, g) => sum + g.items.length, 0);
      const grid = document.getElementById('stats');
      grid.innerHTML = `
        <article class="stat-card p-5 rounded-2xl border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.045)] transition-all duration-200 hover:-translate-y-1 hover:shadow-lg">
          <div class="grid place-items-center w-[38px] h-[38px] rounded-xl text-brand-500 bg-brand-100"><i data-lucide="users" class="w-5 h-5"></i></div>
          <b class="block mt-5 text-[#172033] text-[26px] md:text-[29px] tracking-tight">${roles.length}</b>
          <strong class="block mt-1 text-[#657187] text-sm font-semibold">Roles Registrados</strong>
          <span class="block mt-1.5 text-[#9ba5b5] text-[11px]">Perfiles de acceso disponibles</span>
        </article>

        <article class="stat-card p-5 rounded-2xl border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.045)] transition-all duration-200 hover:-translate-y-1 hover:shadow-lg">
          <div class="grid place-items-center w-[38px] h-[38px] rounded-xl text-blue-600 bg-blue-50"><i data-lucide="shield-half" class="w-5 h-5"></i></div>
          <b class="block mt-5 text-[#172033] text-[26px] md:text-[29px] tracking-tight">${totalModules}</b>
          <strong class="block mt-1 text-[#657187] text-sm font-semibold">Módulos con Permisos</strong>
          <span class="block mt-1.5 text-[#9ba5b5] text-[11px]">Configuración granular por rol</span>
        </article>

        <article class="stat-card p-5 rounded-2xl border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.045)] transition-all duration-200 hover:-translate-y-1 hover:shadow-lg">
          <div class="grid place-items-center w-[38px] h-[38px] rounded-xl text-gold-500 bg-amber-50"><i data-lucide="landmark" class="w-5 h-5"></i></div>
          <b class="block mt-5 text-[#172033] text-[26px] md:text-[29px] tracking-tight">6</b>
          <strong class="block mt-1 text-[#657187] text-sm font-semibold">Carreras Registradas</strong>
          <span class="block mt-1.5 text-[#9ba5b5] text-[11px]">Oferta educativa activa</span>
        </article>

        <article class="stat-card p-5 rounded-2xl border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.045)] transition-all duration-200 hover:-translate-y-1 hover:shadow-lg">
          <div class="grid place-items-center w-[38px] h-[38px] rounded-xl text-emerald-600 bg-emerald-50"><i data-lucide="shield-check" class="w-5 h-5"></i></div>
          <b class="block mt-5 text-[#172033] text-[26px] md:text-[29px] tracking-tight">100%</b>
          <strong class="block mt-1 text-[#657187] text-sm font-semibold">Permisos / ROL</strong>
          <span class="block mt-1.5 text-[#9ba5b5] text-[11px]">Acceso Total Administrador</span>
        </article>
      `;
      lucide.createIcons();
    }

    function renderRoles(filter = '') {
      const tbody = document.getElementById('roles-table-body');
      const empty = document.getElementById('empty-role');
      const normalized = filter.trim().toLowerCase();
      const filtered = roles.filter(role => `${role.name} ${role.desc} ${role.level}`.toLowerCase().includes(normalized));

      tbody.innerHTML = filtered.map(role => {
        const isAdmin = role.id === ADMIN_ROLE_ID;
        const deleteBtn = isAdmin
          ? `<button class="icon-btn grid place-items-center w-[34px] h-[34px] rounded-[9px] border border-[#e9e5e2] text-[#c7cbd2] bg-[#fafafa] cursor-not-allowed" title="El rol Administrador no se puede eliminar" disabled><i data-lucide="lock" class="w-4 h-4"></i></button>`
          : `<button class="icon-btn grid place-items-center w-[34px] h-[34px] rounded-[9px] border border-[#e9e5e2] text-[#687488] bg-white transition-all duration-200 hover:text-red-600 hover:border-red-200 hover:bg-red-50 hover:scale-110" title="Eliminar rol" onclick="deleteRole(${role.id})"><i data-lucide="trash-2" class="w-4 h-4"></i></button>`;

        return `
        <tr class="transition-colors duration-150 hover:bg-[#fcfafb]">
          <td class="px-5 py-4 border-b border-[#f2efed]">
            <div class="flex items-center gap-3 min-w-[220px]">
              <div class="grid place-items-center w-10 h-10 shrink-0 rounded-xl text-brand-500 bg-brand-100"><i data-lucide="${role.icon}" class="w-[19px] h-[19px]"></i></div>
              <div>
                <span class="block text-[#263247] font-extrabold">${role.name}${isAdmin ? ' <span class=\'ml-1 align-middle text-[9px] font-extrabold text-gold-500 bg-amber-50 px-1.5 py-0.5 rounded\'>PROTEGIDO</span>' : ''}</span>
                <span class="block mt-0.5 text-[#99a2b0] text-[11px]">${role.desc}</span>
              </div>
            </div>
          </td>
          <td class="px-5 py-4 border-b border-[#f2efed] text-[#39465a] text-[13px]"><strong>${role.users}</strong></td>
          <td class="px-5 py-4 border-b border-[#f2efed]"><span class="inline-flex py-1 px-2.5 rounded-full text-brand-700 bg-brand-100 text-[10px] font-extrabold">${role.level}</span></td>
          <td class="px-5 py-4 border-b border-[#f2efed]"><span class="inline-flex items-center gap-1.5 text-emerald-700 text-[11px] font-bold"><span class="w-[7px] h-[7px] rounded-full bg-emerald-500 inline-block"></span>Activo</span></td>
          <td class="px-5 py-4 border-b border-[#f2efed]">
            <div class="flex gap-1.5">
              <button class="icon-btn grid place-items-center w-[34px] h-[34px] rounded-[9px] border border-[#e9e5e2] text-[#687488] bg-white transition-all duration-200 hover:text-brand-600 hover:border-[#d9bcc7] hover:bg-[#fff9fb] hover:scale-110" title="Editar rol" onclick="openRoleModal('edit', ${role.id})"><i data-lucide="pencil" class="w-4 h-4"></i></button>
              <button class="icon-btn grid place-items-center w-[34px] h-[34px] rounded-[9px] border border-[#e9e5e2] text-[#687488] bg-white transition-all duration-200 hover:text-brand-600 hover:border-[#d9bcc7] hover:bg-[#fff9fb] hover:scale-110" title="Configurar permisos" onclick="showPermisosView(${role.id})"><i data-lucide="shield-check" class="w-4 h-4"></i></button>
              ${deleteBtn}
            </div>
          </td>
        </tr>`;
      }).join('');

      empty.style.display = filtered.length ? 'none' : 'block';
      document.getElementById('role-count').textContent = `${filtered.length} ${filtered.length === 1 ? 'rol encontrado' : 'roles registrados'}`;
      lucide.createIcons();
    }

    function openRoleModal(mode, id = null) {
      const modal = document.getElementById('role-modal');
      const title = document.getElementById('role-modal-title');
      const subtitle = document.getElementById('role-modal-subtitle');
      const submit = document.getElementById('role-submit-text');
      const permissionBox = document.getElementById('edit-permissions-box');
      const role = roles.find(item => item.id === Number(id));

      editingRoleId = mode === 'edit' ? Number(id) : null;
      if (role) {
        title.textContent = 'Editar rol';
        subtitle.textContent = 'Actualiza la información del perfil seleccionado.';
        submit.textContent = 'Guardar cambios';
        document.getElementById('role-name-input').value = role.name;
        document.getElementById('role-desc-input').value = role.desc;
        document.getElementById('role-level-input').value = role.level;
        permissionBox.classList.remove('hidden');
        permissionBox.classList.add('flex');
        const nameInput = document.getElementById('role-name-input');
        if (role.id === ADMIN_ROLE_ID) { nameInput.setAttribute('readonly', 'true'); } else { nameInput.removeAttribute('readonly'); }
      } else {
        title.textContent = 'Nuevo rol';
        subtitle.textContent = 'Registra un nuevo perfil de acceso para TESCHA.';
        submit.textContent = 'Crear rol';
        document.getElementById('role-name-input').removeAttribute('readonly');
        document.getElementById('role-name-input').value = '';
        document.getElementById('role-desc-input').value = '';
        document.getElementById('role-level-input').value = '';
        permissionBox.classList.add('hidden');
        permissionBox.classList.remove('flex');
      }
      modal.classList.remove('hidden');
      modal.classList.add('grid');
      modal.setAttribute('aria-hidden', 'false');
      setTimeout(() => document.getElementById('role-name-input').focus(), 50);
    }

    function closeRoleModal() {
      const modal = document.getElementById('role-modal');
      modal.classList.add('hidden');
      modal.classList.remove('grid');
      modal.setAttribute('aria-hidden', 'true');
      editingRoleId = null;
    }

    async function saveRole() {
      const name = document.getElementById('role-name-input').value.trim();
      const desc = document.getElementById('role-desc-input').value.trim() || 'Perfil de acceso del sistema';
      const level = document.getElementById('role-level-input').value.trim() || 'Personalizado';
      if (!name) {
        document.getElementById('role-name-input').focus();
        alert('Escribe un nombre para el rol.');
        return;
      }

      try {
        if (editingRoleId) {
          // ===== ACTUALIZAR ROL EN LA BD =====
          const response = await fetch(`{{ url('/api/roles') }}/${editingRoleId}`, {
            method: 'PUT',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': '{{ csrf_token() }}',
              'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ nombre: name, descripcion: desc }),
          });
          const result = await response.json();
          if (!response.ok) {
            alert(result.message || 'Error al actualizar el rol.');
            return;
          }
          showToast('Rol actualizado correctamente.');
        } else {
          // ===== CREAR ROL EN LA BD =====
          const response = await fetch('{{ route("api.roles.store") }}', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': '{{ csrf_token() }}',
              'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ nombre: name, descripcion: desc }),
          });
          const result = await response.json();
          if (!response.ok) {
            alert(result.message || 'Error al crear el rol.');
            return;
          }
          showToast('Rol creado correctamente.');
        }

        closeRoleModal();
        // Recargar roles desde la BD
        await loadRolesFromDB();
      } catch (err) {
        console.error('saveRole:', err);
        alert('Error de conexión al guardar el rol.');
      }
    }

    function openPermissionsFromModal() {
      const id = editingRoleId;
      closeRoleModal();
      if (id) showPermisosView(id);
    }

    async function deleteRole(id) {
      const role = roles.find(item => item.id === Number(id));
      if (!role) return;
      if (role.id === ADMIN_ROLE_ID) {
        alert('El rol "Administrador" no se puede eliminar: es el rol principal del sistema.');
        return;
      }
      if (role.users > 0) {
        alert(`No se puede eliminar "${role.name}" porque tiene ${role.users} usuarios asignados.`);
        return;
      }
      if (!confirm(`¿Eliminar el rol "${role.name}"?`)) return;

      try {
        const response = await fetch(`{{ url('/api/roles') }}/${id}`, {
          method: 'DELETE',
          headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest',
          },
        });
        const result = await response.json();
        if (!response.ok) {
          alert(result.message || 'Error al eliminar el rol.');
          return;
        }
        showToast(`Rol "${role.name}" eliminado.`);
        await loadRolesFromDB();
      } catch (err) {
        console.error('deleteRole:', err);
        alert('Error de conexión al eliminar el rol.');
      }
    }

    function renderPermissionMatrix() {
      const head = document.getElementById('permission-matrix-head');
      const body = document.getElementById('permission-matrix-body');

      head.innerHTML = `
        <tr>
          <th class="sticky left-0 z-10 px-5 py-3.5 text-left bg-[#fcfaf9] border-b border-r border-[#ece9e6] text-[10px] text-[#7d8797] uppercase tracking-[.06em] min-w-[210px]">Módulo</th>
          ${roles.map(role => `
            <th class="px-3 py-3.5 bg-[#fcfaf9] border-b border-[#ece9e6] text-center min-w-[110px]">
              <div class="flex flex-col items-center gap-1.5">
                <div class="grid place-items-center w-8 h-8 rounded-full text-white ${role.id === ADMIN_ROLE_ID ? 'bg-gold-500' : 'bg-brand-500'}"><i data-lucide="${role.icon}" class="w-4 h-4"></i></div>
                <span class="text-[#293448] text-[11px] font-extrabold leading-tight">${role.name}</span>
                ${role.id === ADMIN_ROLE_ID ? '<span class="flex items-center gap-1 text-[9px] font-bold text-gold-500"><i data-lucide=\'lock\' class=\'w-2.5 h-2.5\'></i>fijo</span>' : ''}
              </div>
            </th>
          `).join('')}
        </tr>`;

      body.innerHTML = permissionGroups.map(group => `
        <tr>
          <td colspan="${roles.length + 1}" class="sticky left-0 bg-[#faf8f7] px-5 py-2 border-b border-[#f1efed]">
            <div class="flex items-center gap-2 text-brand-700 text-[11px] font-extrabold uppercase tracking-[.05em]">
              <i data-lucide="${group.icon}" class="w-3.5 h-3.5"></i>${group.title}
            </div>
          </td>
        </tr>
        ${group.items.map(item => `
          <tr class="hover:bg-[#fcfafb] transition-colors duration-150">
            <td class="sticky left-0 z-10 bg-white px-5 py-3 border-b border-r border-[#f1efed] min-w-[210px]">
              <strong class="block text-[#354156] text-xs">${item[0]}</strong>
              <span class="block mt-0.5 text-[#9aa3b1] text-[10px]">${item[1]}</span>
            </td>
            ${roles.map(role => {
              const checked = role.permissions.includes(item[2]) ? 'checked' : '';
              const isAdmin = role.id === ADMIN_ROLE_ID;
              return `<td data-role-col="${role.id}" class="px-3 py-3 border-b border-[#f1efed] text-center transition-colors duration-300">
                <input type="checkbox" data-role="${role.id}" data-permission="${item[2]}" ${checked} ${isAdmin ? 'checked disabled title="El rol Administrador siempre tiene acceso total"' : ''}
                  class="w-[18px] h-[18px] rounded border-2 border-[#d9dde3] cursor-pointer transition-transform duration-150 hover:scale-125 ${isAdmin ? 'opacity-60 cursor-not-allowed accent-gold-500' : ''}">
              </td>`;
            }).join('')}
          </tr>
        `).join('')}
      `).join('');

      lucide.createIcons();
    }

    async function savePermissions() {
      // Construir la matriz de permisos desde los checkboxes del DOM
      const matriz = {};
      roles.forEach(role => {
        if (role.id === ADMIN_ROLE_ID) return;
        matriz[role.id] = {};
        document.querySelectorAll(`input[data-role="${role.id}"]`).forEach(input => {
          matriz[role.id][input.dataset.permission] = input.checked;
        });
      });

      try {
        const response = await fetch('{{ route("api.permisos.store") }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: JSON.stringify({ matriz }),
        });
        const result = await response.json();
        if (!response.ok) {
          alert(result.message || 'Error al guardar permisos.');
          return;
        }
        showToast('Permisos guardados en la base de datos.');
        // Recargar permisos desde la BD
        await loadPermisosFromDB();
      } catch (err) {
        console.error('savePermissions:', err);
        alert('Error de conexión al guardar permisos.');
      }
    }

    /* ---------- Eventos ---------- */
    btnDashboard.addEventListener('click', showDashboardView);
    btnRoles.addEventListener('click', showRolesView);
    btnPermisos.addEventListener('click', () => showPermisosView());

    document.getElementById('role-search').addEventListener('input', event => renderRoles(event.target.value));
    document.getElementById('role-modal').addEventListener('click', event => {
      if (event.target.id === 'role-modal') closeRoleModal();
    });
    document.addEventListener('keydown', event => {
      if (event.key === 'Escape') { closeRoleModal(); }
    });

    // ===== INICIALIZACIÓN: Cargar datos desde la BD =====
    (async function init() {
      await loadRolesFromDB();
      lucide.createIcons();
    })();
  </script>
</body>
</html>