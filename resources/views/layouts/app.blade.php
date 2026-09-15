<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'TESCHA') }}</title>

    <!-- Vite Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script src="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/3.4.1/tailwind.min.js"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              guinda: {
                DEFAULT: '#7A1F3D', // Color Principal (Botones primarios, badges)
                dark: '#5A142C',    // Color Contraste / Encabezados (Sidebar/Header)
              },
              superficie: {
                fondo: '#FFFFFF',   // Fondo Principal Blanco
                tarjeta: '#F3F3F3', // Fondos Secundarios / Tarjetas
              },
              texto: {
                principal: '#333333', // Texto Gris Oscuro
              },
              estado: {
                aprobado: '#22C55E',  // Verde (>= 70)
                reprobado: '#EF4444', // Rojo
                pendiente: '#F59E0B', // Amarillo/Naranja (Segunda Oportunidad)
              }
            }
          }
        }
      }
    </script>
    <style>
      body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; color: #333333; }
      .scrollbar-thin::-webkit-scrollbar { width: 6px; }
      .scrollbar-thin::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 3px; }
    </style>

    @livewireStyles
</head>
<body class="bg-superficie-fondo text-texto-principal antialiased">

<div class="flex h-screen overflow-hidden">

  <!-- SIDEBAR (Vino Oscuro #5A142C) -->
  <aside class="w-64 bg-guinda-dark text-white flex flex-col shrink-0">
    <div class="h-16 flex items-center gap-3 px-5 border-b border-white/10">
      <div class="w-9 h-9 rounded-md bg-white/10 flex items-center justify-center font-bold text-white border border-white/20">
        T
      </div>
      <div class="leading-tight">
        <p class="text-sm font-semibold tracking-wide">TESCHA</p>
        <p class="text-[11px] text-white/60">Control Escolar</p>
      </div>
    </div>

    <nav class="flex-1 overflow-y-auto scrollbar-thin py-4 px-3 space-y-1">
      <p class="px-3 text-[11px] text-white/50 mb-2 font-medium">General</p>
      <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-white/15 text-white font-medium">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        Dashboard
      </a>

      <p class="px-3 text-[11px] text-white/50 mt-5 mb-2 font-medium">Módulos académicos</p>
      <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-white/80 hover:bg-white/10 hover:text-white transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.42A12.02 12.02 0 0112 21.02 12.02 12.02 0 015.84 10.58L12 14z"/></svg>
        Carreras
      </a>
      <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-white/80 hover:bg-white/10 hover:text-white transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        Periodos
      </a>
      <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-white/80 hover:bg-white/10 hover:text-white transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        Alumnos
      </a>
      <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-white/80 hover:bg-white/10 hover:text-white transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0v6m0-6L3 9m9 5l9-5"/></svg>
        Docentes
      </a>

      <p class="px-3 text-[11px] text-white/50 mt-5 mb-2 font-medium">Sistema</p>
      <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-white/80 hover:bg-white/10 hover:text-white transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        Configuración
      </a>
    </nav>

    <div class="p-3 border-t border-white/10">
      <div class="flex items-center gap-3 px-3 py-2">
        <div class="w-8 h-8 rounded-full bg-guinda flex items-center justify-center text-white text-xs font-semibold">
          {{ strtoupper(substr(Auth::user()->name ?? 'AD', 0, 2)) }}
        </div>
        <div class="leading-tight">
          <p class="text-xs font-medium">{{ Auth::user()->name ?? 'Usuario' }}</p>
          <p class="text-[11px] text-white/50">{{ Auth::user()->email ?? 'tescha.edu.mx' }}</p>
        </div>
      </div>
    </div>
  </aside>

  <!-- CONTENT CONTAINER -->
  <div class="flex-1 flex flex-col min-w-0 bg-superficie-fondo">

    <!-- TOPBAR -->
    <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6 shrink-0">
      <div>
        <p class="text-xs text-gray-500">Sistema de Control Escolar</p>
        <h1 class="text-lg font-bold text-texto-principal">Dashboard</h1>
      </div>
      <div class="flex items-center gap-4">
        <button class="relative w-9 h-9 flex items-center justify-center rounded-lg hover:bg-gray-100 transition">
          <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
          <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-estado-pendiente"></span>
        </button>
        <div class="w-px h-6 bg-gray-200"></div>
        <form method="POST" action="/logout">
            @csrf
            <button type="submit" class="flex items-center gap-2 text-xs font-medium text-gray-600 hover:text-guinda transition">
              <div class="w-8 h-8 rounded-full bg-guinda flex items-center justify-center text-white text-xs font-semibold">
                {{ strtoupper(substr(Auth::user()->name ?? 'AD', 0, 2)) }}
              </div>
              <span>Cerrar sesión</span>
            </button>
        </form>
      </div>
    </header>

    <!-- MAIN AREA -->
    <main class="flex-1 overflow-y-auto p-6 space-y-6">
        {{ $slot }}
    </main>
  </div>
</div>

@livewireScripts
</body>
</html>