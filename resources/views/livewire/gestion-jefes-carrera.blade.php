<div>
  <!-- Header -->
  <h1 class="m-0 text-[#202b3d] text-[26px] md:text-[29px] tracking-tight">Gestión de Jefes de Carrera</h1>
  <p class="mt-1.5 mb-6 text-[#748095] text-sm">Administra los Jefes de Carrera asignados y consulta su información de gestión.</p>

  <!-- Barra de búsqueda y botón -->
  <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 p-4 rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_8px_22px_rgba(31,38,50,.045)]">
    <div class="flex items-center gap-2.5 flex-1 sm:max-w-[430px] px-3.5 py-2.5 rounded-[11px] border border-[#e8e4e1] bg-[#faf9f8] text-[#8b95a5]">
      <i data-lucide="search" class="w-[17px]"></i>
      <input type="search" wire:model.live="search" placeholder="Buscar por nombre, carrera o número de empleado..." class="w-full border-0 outline-none bg-transparent text-[#253146] text-[13px] font-medium">
    </div>
    <button type="button" wire:click="abrirModalCrear"
      class="inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-[11px] text-white bg-gradient-to-br from-brand-500 to-brand-700 shadow-[0_7px_16px_rgba(109,25,56,.18)] text-[13px] font-bold transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_10px_20px_rgba(109,25,56,.23)]">
      <i data-lucide="plus" class="w-4 h-4"></i>
      Nuevo Jefe de Carrera
    </button>
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

  <!-- Estadísticas rápidas -->
  <section class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
    <div class="p-4 rounded-[15px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
      <div class="flex items-center gap-3">
        <div class="grid place-items-center w-10 h-10 rounded-xl text-brand-500 bg-brand-100">
          <i data-lucide="award" class="w-5 h-5"></i>
        </div>
        <div>
          <b class="block text-[#293448] text-lg">{{ $jefeCount }}</b>
          <span class="text-[#919aaa] text-[11px]">Total Jefes</span>
        </div>
      </div>
    </div>
    <div class="p-4 rounded-[15px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
      <div class="flex items-center gap-3">
        <div class="grid place-items-center w-10 h-10 rounded-xl text-emerald-600 bg-emerald-50">
          <i data-lucide="user-check" class="w-5 h-5"></i>
        </div>
        <div>
          <b class="block text-[#293448] text-lg">{{ collect($jefes)->where('activo', 1)->count() }}</b>
          <span class="text-[#919aaa] text-[11px]">Activos</span>
        </div>
      </div>
    </div>
    <div class="p-4 rounded-[15px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
      <div class="flex items-center gap-3">
        <div class="grid place-items-center w-10 h-10 rounded-xl text-gold-500 bg-amber-50">
          <i data-lucide="user-x" class="w-5 h-5"></i>
        </div>
        <div>
          <b class="block text-[#293448] text-lg">{{ collect($jefes)->where('activo', 0)->count() }}</b>
          <span class="text-[#919aaa] text-[11px]">Inactivos</span>
        </div>
      </div>
    </div>
    <div class="p-4 rounded-[15px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
      <div class="flex items-center gap-3">
        <div class="grid place-items-center w-10 h-10 rounded-xl text-blue-600 bg-blue-50">
          <i data-lucide="landmark" class="w-5 h-5"></i>
        </div>
        <div>
          <b class="block text-[#293448] text-lg">{{ collect($jefes)->pluck('id_carrera')->unique()->count() }}</b>
          <span class="text-[#919aaa] text-[11px]">Carreras cubiertas</span>
        </div>
      </div>
    </div>
  </section>

  <!-- Tabla de Jefes de Carrera -->
  <section class="mt-4 overflow-hidden rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_8px_22px_rgba(31,38,50,.045)]">
    <div class="flex items-center justify-between px-5 py-5 border-b border-[#efedeb]">
      <div>
        <h2 class="m-0 text-[#293448] text-base">Catálogo de Jefes de Carrera</h2>
        <span class="text-[#919aaa] text-[11px]">Personal directivo asignado en el sistema TESCHA</span>
      </div>
      <span class="text-[#919aaa] text-[11px]">{{ $jefeCount }} registros</span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full border-collapse min-w-[900px]">
        <thead>
          <tr>
            <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Jefe de Carrera</th>
            <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Carrera</th>
            <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">No. Empleado</th>
            <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Periodo</th>
            <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Estatus</th>
            <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Acciones</th>
          </tr>
        </thead>
        <tbody>
          @forelse($jefes as $jefe)
            <tr class="transition-colors duration-150 hover:bg-[#fcfafb]">
              <td class="px-5 py-4 border-b border-[#f2efed]">
                <div class="flex items-center gap-3 min-w-[220px]">
                  <div class="grid place-items-center w-10 h-10 shrink-0 rounded-xl text-brand-500 bg-brand-100">
                    <i data-lucide="award" class="w-[19px] h-[19px]"></i>
                  </div>
                  <div>
                    <span class="block text-[#263247] font-extrabold text-[13px]">
                      {{ $jefe['docente']['nombre'] ?? '—' }} {{ $jefe['docente']['apellido_paterno'] ?? '' }} {{ $jefe['docente']['apellido_materno'] ?? '' }}
                    </span>
                    <span class="block mt-0.5 text-[#99a2b0] text-[11px]">Jefe de Carrera</span>
                  </div>
                </div>
              </td>
              <td class="px-5 py-4 border-b border-[#f2efed] text-[#39465a] text-[13px]">
                <span class="px-1.5 py-0.5 rounded text-white bg-brand-500 text-[10px] font-extrabold mr-2">{{ $jefe['carrera']['clave'] ?? '—' }}</span>
                {{ $jefe['carrera']['nombre'] ?? '—' }}
              </td>
              <td class="px-5 py-4 border-b border-[#f2efed] text-[#39465a] text-[13px]">
                <strong>{{ $jefe['docente']['no_empleado'] ?? '—' }}</strong>
              </td>
              <td class="px-5 py-4 border-b border-[#f2efed] text-[#39465a] text-[13px]">
                @if($jefe['fecha_inicio'] && $jefe['fecha_fin'])
                  {{ \Carbon\Carbon::parse($jefe['fecha_inicio'])->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($jefe['fecha_fin'])->format('d/m/Y') }}
                @elseif($jefe['fecha_inicio'])
                  Desde {{ \Carbon\Carbon::parse($jefe['fecha_inicio'])->format('d/m/Y') }}
                @else
                  <span class="text-[#99a2b0]">Sin periodo definido</span>
                @endif
              </td>
              <td class="px-5 py-4 border-b border-[#f2efed]">
                @if($jefe['activo'])
                  <span class="inline-flex items-center gap-1.5 text-emerald-700 text-[11px] font-bold">
                    <span class="w-[7px] h-[7px] rounded-full bg-emerald-500 inline-block"></span>
                    Activo
                  </span>
                @else
                  <span class="inline-flex items-center gap-1.5 text-[#99a2b0] text-[11px] font-bold">
                    <span class="w-[7px] h-[7px] rounded-full bg-[#c7cbd2] inline-block"></span>
                    Inactivo
                  </span>
                @endif
              </td>
              <td class="px-5 py-4 border-b border-[#f2efed]">
                <div class="flex gap-1.5">
                  <button wire:click="abrirModalEditar({{ $jefe['id_jefe_carrera'] }})" title="Editar Jefe de Carrera"
                    class="grid place-items-center w-[34px] h-[34px] rounded-[9px] border border-[#e9e5e2] text-[#687488] bg-white transition-all duration-200 hover:text-brand-600 hover:border-[#d9bcc7] hover:bg-[#fff9fb] hover:scale-110">
                    <i data-lucide="pencil" class="w-4 h-4"></i>
                  </button>
                  <button wire:click="eliminar({{ $jefe['id_jefe_carrera'] }})" wire:confirm="¿Eliminar este Jefe de Carrera?"
                    title="Eliminar Jefe de Carrera"
                    class="grid place-items-center w-[34px] h-[34px] rounded-[9px] border border-[#e9e5e2] text-[#687488] bg-white transition-all duration-200 hover:text-red-600 hover:border-red-200 hover:bg-red-50 hover:scale-110">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                  </button>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="p-9 text-center text-[#8993a4] text-[13px]">
                <div class="flex flex-col items-center gap-3">
                  <i data-lucide="user-x" class="w-10 h-10 text-[#c1c6cf]"></i>
                  <span>No se encontraron Jefes de Carrera con esa búsqueda.</span>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>

  <!-- Modal Crear / Editar Jefe de Carrera -->
  @if($showModal)
    <div class="fixed inset-0 z-[1000] grid place-items-center p-5 bg-black/55 backdrop-blur-sm" wire:click.self="$set('showModal', false)">
      <div class="w-full max-w-[560px] max-h-[calc(100vh-40px)] overflow-auto rounded-[20px] bg-white shadow-2xl">
        <div class="flex items-start justify-between gap-4 px-6 pt-6 pb-4 border-b border-[#eeeae8]">
          <div>
            <h2 class="m-0 text-[#283348] text-lg">{{ $editingJefeId ? 'Editar Jefe de Carrera' : 'Nuevo Jefe de Carrera' }}</h2>
            <p class="mt-1 mb-0 text-[#919aaa] text-[11px]">{{ $editingJefeId ? 'Actualiza la información del Jefe de Carrera seleccionado.' : 'Asigna un docente como Jefe de Carrera en el sistema TESCHA.' }}</p>
          </div>
          <button type="button" wire:click="$set('showModal', false)"
            class="grid place-items-center w-[34px] h-[34px] rounded-[9px] text-[#788395] bg-[#f7f5f3] transition-colors hover:text-brand-700 hover:bg-brand-100">
            <i data-lucide="x" class="w-4 h-4"></i>
          </button>
        </div>
        <div class="p-6">
          <div class="mb-4">
            <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Docente</label>
            <select wire:model="id_docente"
              class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10 bg-white">
              <option value="">— Selecciona un docente —</option>
              @foreach($docentes as $doc)
                <option value="{{ $doc['id_docente'] }}">
                  {{ $doc['nombre'] }} {{ $doc['apellido_paterno'] }} {{ $doc['apellido_materno'] }} ({{ $doc['no_empleado'] }})
                </option>
              @endforeach
            </select>
            @error('id_docente') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
          </div>

          <div class="mb-4">
            <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Carrera</label>
            <select wire:model="id_carrera"
              class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10 bg-white">
              <option value="">— Selecciona una carrera —</option>
              @foreach($carreras as $carr)
                <option value="{{ $carr['id_carrera'] }}">
                  {{ $carr['clave'] }} — {{ $carr['nombre'] }}
                </option>
              @endforeach
            </select>
            @error('id_carrera') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="mb-2">
              <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Fecha de Inicio</label>
              <input type="date" wire:model="fecha_inicio"
                class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
              @error('fecha_inicio') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>
            <div class="mb-2">
              <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Fecha de Fin</label>
              <input type="date" wire:model="fecha_fin"
                class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
              @error('fecha_fin') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>
          </div>
        </div>
        <div class="flex justify-end gap-2 px-6 pb-6 pt-2 border-t border-[#eeeae8] mt-2">
          <button type="button" wire:click="$set('showModal', false)"
            class="py-2.5 px-4 rounded-[10px] border border-[#e5e1de] text-[#697487] bg-white font-bold text-xs transition-colors hover:bg-[#faf9f8]">
            Cancelar
          </button>
          <button type="button" wire:click="guardar"
            class="inline-flex items-center gap-2 py-2.5 px-4 rounded-[11px] text-white bg-gradient-to-br from-brand-500 to-brand-700 shadow-[0_7px_16px_rgba(109,25,56,.18)] font-bold text-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_10px_20px_rgba(109,25,56,.23)]">
            <i data-lucide="check" class="w-4 h-4"></i>
            {{ $editingJefeId ? 'Guardar cambios' : 'Registrar Jefe de Carrera' }}
          </button>
        </div>
      </div>
    </div>
  @endif

  @script
  <script>
    // Re-inicializar iconos Lucide después de cada actualización de Livewire
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
  @endscript
</div>
