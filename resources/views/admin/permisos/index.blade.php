<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>TESCHA | Gestión de Permisos</title>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', '-apple-system', 'Segoe UI', 'sans-serif'] },
          colors: {
            brand: { 50:'#fce8ef', 100:'#f8edf1', 400:'#8c2448', 500:'#8b2346', 600:'#791e41', 700:'#6d1938', 800:'#56132d' },
            gold:  { 300:'#f5d891', 400:'#f4d894', 500:'#c96b00' },
            cream: '#f6f4f2',
            ink:   '#791e41'
          }
        }
      }
    }
  </script>
  <style>
    /* Checkbox personalizado */
    .check-tescha {
      -webkit-appearance: none;
      -moz-appearance: none;
      appearance: none;
      width: 22px;
      height: 22px;
      border: 2px solid #d1d5db;
      border-radius: 6px;
      background-color: #fff;
      cursor: pointer;
      transition: all 0.2s ease;
      position: relative;
      display: inline-block;
    }
    .check-tescha:hover:not(:disabled) {
      border-color: #8b2346;
      box-shadow: 0 0 0 3px rgba(139,35,70,0.1);
    }
    .check-tescha:checked {
      background-color: #9f2153;
      border-color: #c9c551;
    }
    .check-tescha:checked::after {
      content: '';
      position: absolute;
      left: 5px;
      top: 1px;
      width: 7px;
      height: 12px;
      border: solid white;
      border-width: 0 2.5px 2.5px 0;
      transform: rotate(45deg);
    }
    .check-tescha:disabled {
      background-color: #f3f4f6;
      border-color: #d1d5db;
      cursor: not-allowed;
      opacity: 0.6;
    }
    .check-tescha:disabled:checked {
      background-color: #8b2346;
      border-color: #8b2346;
      opacity: 0.8;
    }
    /* Filas */
    .row-hover:hover { background-color: #fdf2f5; }
  </style>
</head>
<body class="bg-cream text-ink font-sans min-h-screen">

  <!-- ENCABEZADO -->
  <header class="bg-gradient-to-r from-brand-800 to-brand-700 text-white px-6 py-4 shadow-lg">
    <div class="max-w-6xl mx-auto flex items-center justify-between">
      <a href="{{ route('dashboard') }}" class="flex items-center gap-2 text-white/70 hover:text-white transition text-sm font-medium">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Volver al Panel
      </a>
      <div class="flex items-center gap-3">
        <div class="grid place-items-center w-9 h-9 rounded-xl text-gold-400 bg-white/10">
          <i data-lucide="shield-check" class="w-5 h-5"></i>
        </div>
        <h1 class="text-lg font-bold tracking-tight">Gestión de Permisos</h1>
      </div>
      <div class="w-32"></div>
    </div>
  </header>

  <main class="max-w-6xl mx-auto px-6 py-8">

    <!-- Selector de Rol -->
    <div class="bg-white rounded-2xl shadow-[0_4px_20px_rgba(31,38,50,.06)] border border-[#ece9e6] p-5 mb-6">
      <form method="GET" action="{{ route('permisos.index') }}" class="flex items-center gap-4">
        <div class="flex items-center gap-2.5">
          <div class="grid place-items-center w-9 h-9 rounded-xl text-brand-500 bg-brand-50">
            <i data-lucide="users" class="w-[18px] h-[18px]"></i>
          </div>
          <label for="role_id" class="text-sm font-bold text-ink whitespace-nowrap">Seleccionar Rol:</label>
        </div>
        <select
          name="role_id"
          id="role_id"
          onchange="this.form.submit()"
          class="border border-[#e3dfdc] rounded-xl px-4 py-2.5 text-sm font-semibold text-ink bg-[#faf9f8] focus:outline-none focus:ring-4 focus:ring-brand-500/10 focus:border-brand-400 min-w-[240px] transition-all"
        >
          @foreach($roles as $role)
            <option value="{{ $role->id_rol }}" {{ $roleSeleccionado && $roleSeleccionado->id_rol == $role->id_rol ? 'selected' : '' }}>
              {{ $role->nombre }}
            </option>
          @endforeach
        </select>

        @if($roleSeleccionado && $roleSeleccionado->id_rol == 1)
          <span class="flex items-center gap-1.5 text-gold-500 text-xs font-extrabold bg-amber-50 px-3 py-1.5 rounded-full border border-amber-200">
            <i data-lucide="lock" class="w-3 h-3"></i>
            Acceso total — No editable
          </span>
        @endif
      </form>
    </div>

    @if(session('success'))
      <div class="flash-msg bg-emerald-50 border border-emerald-200 text-emerald-700 px-5 py-3.5 rounded-2xl mb-6 text-sm font-semibold shadow-sm flex items-center gap-2">
        <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500"></i>
        {{ session('success') }}
      </div>
    @endif

    @if(session('error'))
      <div class="flash-msg bg-red-50 border border-red-200 text-red-700 px-5 py-3.5 rounded-2xl mb-6 text-sm font-semibold shadow-sm flex items-center gap-2">
        <i data-lucide="alert-circle" class="w-5 h-5 text-red-500"></i>
        {{ session('error') }}
      </div>
    @endif

    <!-- Matriz de Permisos -->
    @if($roleSeleccionado)
    <form method="POST" action="{{ route('permisos.update') }}" id="form-permisos">
      @csrf
      <input type="hidden" name="role_id" value="{{ $roleSeleccionado->id_rol }}">

      <div class="bg-white rounded-2xl shadow-[0_8px_30px_rgba(31,38,50,.07)] border border-[#ece9e6] overflow-hidden">
        <table class="w-full text-sm">
          <thead>
            <tr class="bg-gradient-to-r from-ink to-[#2d3a50]">
              <th class="text-left px-6 py-4 font-bold text-[11px] text-white/80 uppercase tracking-[.08em]">Módulo</th>
              <th class="text-center px-6 py-4 font-bold text-[11px] text-white/80 uppercase tracking-[.08em] w-28">Ver</th>
              <th class="text-center px-6 py-4 font-bold text-[11px] text-white/80 uppercase tracking-[.08em] w-28">Crear</th>
              <th class="text-center px-6 py-4 font-bold text-[11px] text-white/80 uppercase tracking-[.08em] w-28">Editar</th>
              <th class="text-center px-6 py-4 font-bold text-[11px] text-white/80 uppercase tracking-[.08em] w-28">Eliminar</th>
            </tr>
          </thead>
          <tbody>
            @php $rowIndex = 0; @endphp
            @foreach($permisos as $modulo => $acciones)
              <tr class="{{ $rowIndex % 2 == 1 ? 'bg-[#faf9f8]' : 'bg-white' }} border-b border-[#f1efed] row-hover transition-colors">
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="grid place-items-center w-8 h-8 rounded-lg text-brand-500 bg-brand-50 shrink-0">
                      <i data-lucide="folder" class="w-4 h-4"></i>
                    </div>
                    <span class="font-bold text-ink text-[13px]">{{ $modulo }}</span>
                  </div>
                </td>

                @foreach(['ver', 'crear', 'editar', 'eliminar'] as $accion)
                  @php
                    $permiso = $acciones->firstWhere('accion', $accion);
                    $isAdmin = $roleSeleccionado && $roleSeleccionado->id_rol == 1;
                  @endphp
                  <td class="text-center px-6 py-4">
                    @if($permiso)
                      <label class="inline-flex items-center justify-center cursor-pointer" title="{{ $isAdmin ? 'Permiso fijo del Administrador' : '' }}">
                        <input
                          type="checkbox"
                          name="permisos[]"
                          value="{{ $permiso->id_permiso }}"
                          {{ in_array($permiso->id_permiso, $permisosExistentes) ? 'checked' : '' }}
                          {{ $isAdmin ? 'disabled' : '' }}
                          class="check-tescha"
                        >
                      </label>
                    @else
                      <span class="text-gray-300 text-lg">—</span>
                    @endif
                  </td>
                @endforeach
              </tr>
              @php $rowIndex++; @endphp
            @endforeach
          </tbody>
        </table>
      </div>

      <!-- Botón Guardar -->
      @if(!($roleSeleccionado && $roleSeleccionado->id_rol == 1))
      <div class="flex justify-end mt-6">
        <button
          type="submit"
          class="inline-flex items-center gap-2 bg-gradient-to-br from-brand-500 to-brand-700 text-white font-bold py-3 px-8 rounded-2xl text-sm shadow-[0_7px_16px_rgba(109,25,56,.18)] hover:shadow-[0_10px_20px_rgba(109,25,56,.25)] transition-all duration-200 hover:-translate-y-0.5"
        >
          <i data-lucide="save" class="w-4 h-4"></i>
          Guardar Cambios
        </button>
      </div>
      @endif
    </form>
    @endif

  </main>

  <script>
    lucide.createIcons();
    // Auto-ocultar mensajes flash después de 3 segundos
    document.querySelectorAll('.flash-msg').forEach(function(el) {
      setTimeout(function() {
        el.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
        el.style.opacity = '0';
        el.style.transform = 'translateY(-6px)';
        setTimeout(function() { el.remove(); }, 400);
      }, 3000);
    });
  </script>
</body>
</html>
