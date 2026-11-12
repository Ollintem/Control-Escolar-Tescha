<div>
  <!-- Header -->
  <h1 class="m-0 text-[#202b3d] text-[26px] md:text-[29px] tracking-tight">Gestión de Carreras</h1>
  <p class="mt-1.5 mb-6 text-[#748095] text-sm">Administra las carreras del sistema y consulta su información académica.</p>

  <!-- Barra de búsqueda y botón -->
  <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 p-4 rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_8px_22px_rgba(31,38,50,.045)]">
    <div class="flex items-center gap-2.5 flex-1 sm:max-w-[430px] px-3.5 py-2.5 rounded-[11px] border border-[#e8e4e1] bg-[#faf9f8] text-[#8b95a5]">
      <i data-lucide="search" class="w-[17px]"></i>
      <input type="search" wire:model.live="search" placeholder="Buscar por clave o nombre de la carrera..." class="w-full border-0 outline-none bg-transparent text-[#253146] text-[13px] font-medium">
    </div>
    @if($puedeCrear)
      <button type="button" wire:click="abrirModalCrear"
        class="inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-[11px] text-white bg-gradient-to-br from-brand-500 to-brand-700 shadow-[0_7px_16px_rgba(109,25,56,.18)] text-[13px] font-bold transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_10px_20px_rgba(109,25,56,.23)]">
        <i data-lucide="plus" class="w-4 h-4"></i>
        Nueva Carrera
      </button>
    @endif
  </div>

  <!-- Mensajes flash (se ocultan automáticamente en 3s) -->
  @if(session('success'))
    <div class="flash-msg mt-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-[11px] text-sm font-semibold shadow-sm flex items-center gap-2">
      <i data-lucide="check-circle" class="w-4 h-4 text-emerald-500"></i>
      {{ session('success') }}
    </div>
  @endif

  @if(session('error'))
    <div class="flash-msg mt-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-[11px] text-sm font-semibold shadow-sm flex items-center gap-2">
      <i data-lucide="alert-circle" class="w-4 h-4 text-red-500"></i>
      {{ session('error') }}
    </div>
  @endif

  <!-- ============================================================
       ADMINISTRACIÓN DE LA CARRERA
       Acceso rápido desde el Dashboard (clic en nombre o flecha).
       Información general + conteos de relaciones existentes.
       ============================================================ -->
  @if($carreraDetalle)
    <section class="mt-4 overflow-hidden rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_8px_22px_rgba(31,38,50,.045)]">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 py-5 border-b border-[#efedeb]">
        <div class="flex items-center gap-3">
          <div class="grid place-items-center w-10 h-10 rounded-xl text-brand-500 bg-brand-100">
            <i data-lucide="landmark" class="w-5 h-5"></i>
          </div>
          <div>
            <h2 class="m-0 text-[#293448] text-base">Administración de la carrera</h2>
            <span class="text-[#919aaa] text-[11px]">{{ $carreraDetalle['nombre'] }}</span>
          </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          @if($puedeEditar)
            <button type="button" wire:click="abrirModalEditar({{ $carreraDetalle['id_carrera'] }})"
              class="inline-flex items-center gap-2 py-2.5 px-4 rounded-[11px] text-white bg-gradient-to-br from-brand-500 to-brand-700 shadow-[0_7px_16px_rgba(109,25,56,.18)] text-[13px] font-bold transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_10px_20px_rgba(109,25,56,.23)]">
              <i data-lucide="pencil" class="w-4 h-4"></i>
              Editar información
            </button>
          @endif
          <button type="button" wire:click="cerrarDetalleCarrera"
            class="py-2.5 px-4 rounded-[10px] border border-[#e5e1de] text-[#697487] bg-white font-bold text-xs transition-colors hover:bg-[#faf9f8]">
            Cerrar
          </button>
        </div>
      </div>

      <!-- Información general -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 px-5 py-5 border-b border-[#f1efed]">
        <div>
          <span class="block mb-1.5 text-[#919aaa] text-[11px] font-extrabold uppercase tracking-[.07em]">Clave oficial</span>
          <span class="inline-block px-1.5 py-0.5 rounded text-white bg-brand-500 text-[11px] font-extrabold">{{ $carreraDetalle['clave'] }}</span>
        </div>
        <div>
          <span class="block mb-1.5 text-[#919aaa] text-[11px] font-extrabold uppercase tracking-[.07em]">Rétícula oficial</span>
          @if($carreraDetalle['reticula_oficial'])
            <span class="inline-flex items-center gap-1.5 text-blue-700 text-[13px] font-semibold">
              <i data-lucide="file-check" class="w-3.5 h-3.5"></i>
              {{ $carreraDetalle['reticula_oficial'] }}
            </span>
          @else
            <span class="text-[#99a2b0] text-[12px]">Sin rétícula asignada</span>
          @endif
        </div>
        <div>
          <span class="block mb-1.5 text-[#919aaa] text-[11px] font-extrabold uppercase tracking-[.07em]">Estado</span>
          @if($carreraDetalle['activo'])
            <span class="inline-flex items-center gap-1.5 text-emerald-700 text-[12px] font-bold">
              <span class="w-[7px] h-[7px] rounded-full bg-emerald-500 inline-block"></span>
              Carrera activa
            </span>
          @else
            <span class="inline-flex items-center gap-1.5 text-[#99a2b0] text-[12px] font-bold">
              <span class="w-[7px] h-[7px] rounded-full bg-[#c7cbd2] inline-block"></span>
              Carrera inactiva
            </span>
          @endif
        </div>
      </div>

      <!-- Contenido académico relacionado -->
      <div class="px-5 py-5">
        <span class="block mb-3 text-[#919aaa] text-[11px] font-extrabold uppercase tracking-[.07em]">Contenido académico</span>
        <div class="grid grid-cols-2 lg:grid-cols-6 gap-3">
          @php
            $resumenCarrera = [
              ['icono' => 'calendar-days', 'valor' => $carreraDetalle['semestres'], 'texto' => 'Semestres', 'color' => 'text-brand-500 bg-brand-100'],
              ['icono' => 'book-open',     'valor' => $carreraDetalle['materias'],  'texto' => 'Materias',  'color' => 'text-blue-600 bg-blue-50'],
              ['icono' => 'award',         'valor' => $carreraDetalle['jefes'],     'texto' => 'Jefes',     'color' => 'text-gold-500 bg-amber-50'],
              ['icono' => 'grid-2x2',      'valor' => $carreraDetalle['grupos'],    'texto' => 'Grupos',    'color' => 'text-emerald-600 bg-emerald-50'],
              ['icono' => 'users-round',   'valor' => $carreraDetalle['alumnos'],   'texto' => 'Alumnos',   'color' => 'text-brand-500 bg-brand-100'],
              ['icono' => 'user-cog',      'valor' => $carreraDetalle['docentes'],  'texto' => 'Docentes',  'color' => 'text-blue-600 bg-blue-50'],
            ];
          @endphp
          @foreach($resumenCarrera as $item)
            <div class="p-4 rounded-[15px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)] text-center">
              <div class="grid place-items-center w-9 h-9 mx-auto mb-2 rounded-xl {{ $item['color'] }}">
                <i data-lucide="{{ $item['icono'] }}" class="w-[18px]"></i>
              </div>
              <b class="block text-[#293448] text-lg">{{ $item['valor'] }}</b>
              <span class="text-[#919aaa] text-[11px]">{{ $item['texto'] }}</span>
            </div>
          @endforeach
        </div>
      </div>
    </section>
  @endif

  <!-- Estadísticas rápidas -->
  <section class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
    <div class="p-4 rounded-[15px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
      <div class="flex items-center gap-3">
        <div class="grid place-items-center w-10 h-10 rounded-xl text-brand-500 bg-brand-100">
          <i data-lucide="landmark" class="w-5 h-5"></i>
        </div>
        <div>
          <b class="block text-[#293448] text-lg">{{ $carreraCount }}</b>
          <span class="text-[#919aaa] text-[11px]">Total carreras</span>
        </div>
      </div>
    </div>
    <div class="p-4 rounded-[15px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
      <div class="flex items-center gap-3">
        <div class="grid place-items-center w-10 h-10 rounded-xl text-emerald-600 bg-emerald-50">
          <i data-lucide="check-circle" class="w-5 h-5"></i>
        </div>
        <div>
          <b class="block text-[#293448] text-lg">{{ collect($carreras)->where('activo', 1)->count() }}</b>
          <span class="text-[#919aaa] text-[11px]">Activas</span>
        </div>
      </div>
    </div>
    <div class="p-4 rounded-[15px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
      <div class="flex items-center gap-3">
        <div class="grid place-items-center w-10 h-10 rounded-xl text-gold-500 bg-amber-50">
          <i data-lucide="x-circle" class="w-5 h-5"></i>
        </div>
        <div>
          <b class="block text-[#293448] text-lg">{{ collect($carreras)->where('activo', 0)->count() }}</b>
          <span class="text-[#919aaa] text-[11px]">Inactivas</span>
        </div>
      </div>
    </div>
    <div class="p-4 rounded-[15px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
      <div class="flex items-center gap-3">
        <div class="grid place-items-center w-10 h-10 rounded-xl text-blue-600 bg-blue-50">
          <i data-lucide="file-text" class="w-5 h-5"></i>
        </div>
        <div>
          <b class="block text-[#293448] text-lg">{{ collect($carreras)->whereNotNull('reticula_oficial')->count() }}</b>
          <span class="text-[#919aaa] text-[11px]">Con rétícula</span>
        </div>
      </div>
    </div>
  </section>

  <!-- Tabla de Carreras -->
  <section class="mt-4 overflow-hidden rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_8px_22px_rgba(31,38,50,.045)]">
    <div class="flex items-center justify-between px-5 py-5 border-b border-[#efedeb]">
      <div>
        <h2 class="m-0 text-[#293448] text-base">Catálogo de Carreras</h2>
        <span class="text-[#919aaa] text-[11px]">Carreras registradas en el sistema TESCHA</span>
      </div>
      <span class="text-[#919aaa] text-[11px]">{{ $carreraCount }} carreras registradas</span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full border-collapse min-w-[860px]">
        <thead>
          <tr>
            <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Carrera</th>
            <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Clave</th>
            <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Rétícula Oficial</th>
            <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Estatus</th>
            <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Acciones</th>
          </tr>
        </thead>
        <tbody>
          @forelse($carreras as $carrera)
            <tr class="transition-colors duration-150 hover:bg-[#fcfafb]">
              <td class="px-5 py-4 border-b border-[#f2efed]">
                <div class="flex items-center gap-3 min-w-[220px]">
                  <div class="grid place-items-center w-10 h-10 shrink-0 rounded-xl text-brand-500 bg-brand-100">
                    <i data-lucide="landmark" class="w-[19px] h-[19px]"></i>
                  </div>
                  <div>
                    <span class="block text-[#263247] font-extrabold text-[13px]">
                      {{ $carrera['nombre'] }}
                    </span>
                    <span class="block mt-0.5 text-[#99a2b0] text-[11px]">Carrera</span>
                  </div>
                </div>
              </td>
              <td class="px-5 py-4 border-b border-[#f2efed] text-[#39465a] text-[13px]">
                <span class="px-1.5 py-0.5 rounded text-white bg-brand-500 text-[10px] font-extrabold">{{ $carrera['clave'] }}</span>
              </td>
              <td class="px-5 py-4 border-b border-[#f2efed] text-[#39465a] text-[13px]">
                @if($carrera['reticula_oficial'])
                  <span class="inline-flex items-center gap-1.5 text-blue-700 text-[12px] font-semibold">
                    <i data-lucide="file-check" class="w-3.5 h-3.5"></i>
                    {{ $carrera['reticula_oficial'] }}
                  </span>
                @else
                  <span class="text-[#99a2b0] text-[12px]">Sin rétícula asignada</span>
                @endif
              </td>
              <td class="px-5 py-4 border-b border-[#f2efed]">
                @if($carrera['activo'])
                  <span class="inline-flex items-center gap-1.5 text-emerald-700 text-[11px] font-bold">
                    <span class="w-[7px] h-[7px] rounded-full bg-emerald-500 inline-block"></span>
                    Activa
                  </span>
                @else
                  <span class="inline-flex items-center gap-1.5 text-[#99a2b0] text-[11px] font-bold">
                    <span class="w-[7px] h-[7px] rounded-full bg-[#c7cbd2] inline-block"></span>
                    Inactiva
                  </span>
                @endif
              </td>
              <td class="px-5 py-4 border-b border-[#f2efed]">
                <div class="flex gap-1.5">
                  @if($puedeEditar)
                    <button wire:click="abrirModalEditar({{ $carrera['id_carrera'] }})" title="Editar carrera"
                      class="grid place-items-center w-[34px] h-[34px] rounded-[9px] border border-[#e9e5e2] text-[#687488] bg-white transition-all duration-200 hover:text-brand-600 hover:border-[#d9bcc7] hover:bg-[#fff9fb] hover:scale-110">
                      <i data-lucide="pencil" class="w-4 h-4"></i>
                    </button>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="p-9 text-center text-[#8993a4] text-[13px]">
                <div class="flex flex-col items-center gap-3">
                  <i data-lucide="landmark" class="w-10 h-10 text-[#c1c6cf]"></i>
                  <span>No se encontraron carreras con esa búsqueda.</span>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>

  <!-- Modal Crear / Editar Carrera -->
  @if($showModal)
    <div class="fixed inset-0 z-[1000] grid place-items-center p-5 bg-black/55 backdrop-blur-sm" wire:click.self="$set('showModal', false)">
      <div class="w-full max-w-[560px] max-h-[calc(100vh-40px)] overflow-auto rounded-[20px] bg-white shadow-2xl">
        <div class="flex items-start justify-between gap-4 px-6 pt-6 pb-4 border-b border-[#eeeae8]">
          <div>
            <h2 class="m-0 text-[#283348] text-lg">{{ $editingCarreraId ? 'Editar carrera' : 'Nueva carrera' }}</h2>
            <p class="mt-1 mb-0 text-[#919aaa] text-[11px]">{{ $editingCarreraId ? 'Actualiza la información de la carrera seleccionada.' : 'Registra una nueva carrera en el sistema TESCHA.' }}</p>
          </div>
          <button type="button" wire:click="$set('showModal', false)"
            class="grid place-items-center w-[34px] h-[34px] rounded-[9px] text-[#788395] bg-[#f7f5f3] transition-colors hover:text-brand-700 hover:bg-brand-100">
            <i data-lucide="x" class="w-4 h-4"></i>
          </button>
        </div>
        <div class="p-6">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="mb-2">
              <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Clave</label>
              <input type="text" wire:model="clave" placeholder="Ej. ISC"
                class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
              @error('clave') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>
            <div class="mb-2">
              <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Nombre de la Carrera</label>
              <input type="text" wire:model="nombre" placeholder="Ej. Ingeniería en Sistemas Computacionales"
                class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
              @error('nombre') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>
          </div>
          <div class="mb-4 mt-2">
            <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Rétícula Oficial <span class="text-[#919aaa] font-normal">(opcional)</span></label>
            <input type="text" wire:model="reticula_oficial" placeholder="Ej. Reticula-ISC-2024"
              class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
            @error('reticula_oficial') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
          </div>

          <div class="mb-2 flex items-center gap-2 p-3 rounded-[10px] border border-[#e3dfdc] bg-[#faf9f8]">
            <label class="inline-flex items-center gap-2 cursor-pointer">
              <input type="checkbox" wire:model="activo" class="w-4 h-4 text-brand-500 rounded border-[#cfc9c5] focus:ring-brand-500/25">
              <span class="text-[#4b5769] text-[12px] font-extrabold">Carrera activa</span>
            </label>
            <span class="ml-auto text-[#99a2b0] text-[11px]">Desactiva la carrera sin eliminarla</span>
          </div>
          @error('activo') <span class="text-red-500 text-xs block">{{ $message }}</span> @enderror
        </div>
        <div class="flex justify-end gap-2 px-6 pb-6 pt-2 border-t border-[#eeeae8] mt-2">
          <button type="button" wire:click="$set('showModal', false)"
            class="py-2.5 px-4 rounded-[10px] border border-[#e5e1de] text-[#697487] bg-white font-bold text-xs transition-colors hover:bg-[#faf9f8]">
            Cancelar
          </button>
          <button type="button" wire:click="guardar"
            class="inline-flex items-center gap-2 py-2.5 px-4 rounded-[11px] text-white bg-gradient-to-br from-brand-500 to-brand-700 shadow-[0_7px_16px_rgba(109,25,56,.18)] font-bold text-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_10px_20px_rgba(109,25,56,.23)]">
            <i data-lucide="check" class="w-4 h-4"></i>
            {{ $editingCarreraId ? 'Guardar cambios' : 'Registrar carrera' }}
          </button>
        </div>
      </div>
    </div>
  @endif

  @script
  <script>

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
  @endscript
</div>
