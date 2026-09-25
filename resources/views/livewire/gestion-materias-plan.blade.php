<div>
  <!-- Header -->
  <h1 class="m-0 text-[#202b3d] text-[26px] md:text-[29px] tracking-tight">Gestión de Materias y Plan</h1>
  <p class="mt-1.5 mb-6 text-[#748095] text-sm">Administra las materias y los planes de estudio del sistema.</p>

  <!-- Barra de búsqueda y botón -->
  <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 p-4 rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_8px_22px_rgba(31,38,50,.045)]">
    <div class="flex items-center gap-2.5 flex-1 sm:max-w-[430px] px-3.5 py-2.5 rounded-[11px] border border-[#e8e4e1] bg-[#faf9f8] text-[#8b95a5]">
      <i data-lucide="search" class="w-[17px]"></i>
      @if($tab === 'materias')
        <input type="search" wire:model.live="materiaSearch" placeholder="Buscar por clave, nombre o carrera..." class="w-full border-0 outline-none bg-transparent text-[#253146] text-[13px] font-medium">
      @else
        <input type="search" wire:model.live="planSearch" placeholder="Buscar por clave, nombre o carrera..." class="w-full border-0 outline-none bg-transparent text-[#253146] text-[13px] font-medium">
      @endif
    </div>
    @if($tab === 'materias')
      <button type="button" wire:click="abrirModalMateriaCrear"
        class="inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-[11px] text-white bg-gradient-to-br from-brand-500 to-brand-700 shadow-[0_7px_16px_rgba(109,25,56,.18)] text-[13px] font-bold transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_10px_20px_rgba(109,25,56,.23)]">
        <i data-lucide="plus" class="w-4 h-4"></i>
        Nueva Materia
      </button>
    @else
      <button type="button" wire:click="abrirModalPlanCrear"
        class="inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-[11px] text-white bg-gradient-to-br from-brand-500 to-brand-700 shadow-[0_7px_16px_rgba(109,25,56,.18)] text-[13px] font-bold transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_10px_20px_rgba(109,25,56,.23)]">
        <i data-lucide="plus" class="w-4 h-4"></i>
        Nuevo Plan
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

  <!-- Pestañas -->
  <div class="flex gap-2 mt-5 mb-1">
    <button type="button" wire:click="switchTab('materias')"
      class="inline-flex items-center gap-2 py-2.5 px-4 rounded-[11px] text-[13px] font-bold transition-all duration-200 {{ $tab === 'materias'
        ? 'text-white bg-gradient-to-br from-brand-500 to-brand-700 shadow-[0_7px_16px_rgba(109,25,56,.18)]'
        : 'text-[#697487] bg-white border border-[#e5e1de] hover:bg-[#faf9f8]' }}">
      <i data-lucide="book-open" class="w-4 h-4"></i>
      Materias
      <span class="px-1.5 py-0.5 rounded-full text-[10px] font-extrabold {{ $tab === 'materias' ? 'bg-white/20 text-white' : 'bg-[#f0eeec] text-[#748095]' }}">{{ $materiaCount }}</span>
    </button>
    <button type="button" wire:click="switchTab('planes')"
      class="inline-flex items-center gap-2 py-2.5 px-4 rounded-[11px] text-[13px] font-bold transition-all duration-200 {{ $tab === 'planes'
        ? 'text-white bg-gradient-to-br from-brand-500 to-brand-700 shadow-[0_7px_16px_rgba(109,25,56,.18)]'
        : 'text-[#697487] bg-white border border-[#e5e1de] hover:bg-[#faf9f8]' }}">
      <i data-lucide="file-text" class="w-4 h-4"></i>
      Planes de Estudio
      <span class="px-1.5 py-0.5 rounded-full text-[10px] font-extrabold {{ $tab === 'planes' ? 'bg-white/20 text-white' : 'bg-[#f0eeec] text-[#748095]' }}">{{ $planCount }}</span>
    </button>
  </div>

  <!-- ============================================================
       PESTAÑA: MATERIAS
  ============================================================ -->
  @if($tab === 'materias')

    <!-- Estadísticas -->
    <section class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
      <div class="p-4 rounded-[15px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
        <div class="flex items-center gap-3">
          <div class="grid place-items-center w-10 h-10 rounded-xl text-brand-500 bg-brand-100">
            <i data-lucide="book-open" class="w-5 h-5"></i>
          </div>
          <div>
            <b class="block text-[#293448] text-lg">{{ $materiaCount }}</b>
            <span class="text-[#919aaa] text-[11px]">Total materias</span>
          </div>
        </div>
      </div>
      <div class="p-4 rounded-[15px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
        <div class="flex items-center gap-3">
          <div class="grid place-items-center w-10 h-10 rounded-xl text-emerald-600 bg-emerald-50">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
          </div>
          <div>
            <b class="block text-[#293448] text-lg">{{ collect($materias)->where('activo', 1)->count() }}</b>
            <span class="text-[#919aaa] text-[11px]">Activas</span>
          </div>
        </div>
      </div>
      <div class="p-4 rounded-[15px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
        <div class="flex items-center gap-3">
          <div class="grid place-items-center w-10 h-10 rounded-xl text-gold-500 bg-amber-50">
            <i data-lucide="star" class="w-5 h-5"></i>
          </div>
          <div>
            <b class="block text-[#293448] text-lg">{{ collect($materias)->sum('creditos') }}</b>
            <span class="text-[#919aaa] text-[11px]">Total créditos</span>
          </div>
        </div>
      </div>
      <div class="p-4 rounded-[15px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
        <div class="flex items-center gap-3">
          <div class="grid place-items-center w-10 h-10 rounded-xl text-blue-600 bg-blue-50">
            <i data-lucide="landmark" class="w-5 h-5"></i>
          </div>
          <div>
            <b class="block text-[#293448] text-lg">{{ collect($materias)->pluck('id_carrera')->unique()->count() }}</b>
            <span class="text-[#919aaa] text-[11px]">Carreras con materias</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Tabla de Materias -->
    <section class="mt-4 overflow-hidden rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_8px_22px_rgba(31,38,50,.045)]">
      <div class="flex items-center justify-between px-5 py-5 border-b border-[#efedeb]">
        <div>
          <h2 class="m-0 text-[#293448] text-base">Catálogo de Materias</h2>
          <span class="text-[#919aaa] text-[11px]">Materias registradas en el sistema TESCHA</span>
        </div>
        <span class="text-[#919aaa] text-[11px]">{{ $materiaCount }} registros</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full border-collapse min-w-[960px]">
          <thead>
            <tr>
              <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Materia</th>
              <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Clave</th>
              <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Carrera</th>
              <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Créditos</th>
              <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Horas (T/P)</th>
              <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Estatus</th>
              <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Acciones</th>
            </tr>
          </thead>
          <tbody>
            @forelse($materias as $mat)
              <tr class="transition-colors duration-150 hover:bg-[#fcfafb]">
                <td class="px-5 py-4 border-b border-[#f2efed]">
                  <div class="flex items-center gap-3 min-w-[220px]">
                    <div class="grid place-items-center w-10 h-10 shrink-0 rounded-xl text-brand-500 bg-brand-100">
                      <i data-lucide="book-open" class="w-[19px] h-[19px]"></i>
                    </div>
                    <div>
                      <span class="block text-[#263247] font-extrabold text-[13px]">{{ $mat['nombre'] }}</span>
                      <span class="block mt-0.5 text-[#99a2b0] text-[11px]">{{ $mat['carrera']['nombre'] ?? '—' }}</span>
                    </div>
                  </div>
                </td>
                <td class="px-5 py-4 border-b border-[#f2efed] text-[#39465a] text-[13px]">
                  <span class="px-1.5 py-0.5 rounded text-white bg-brand-500 text-[10px] font-extrabold">{{ $mat['clave'] }}</span>
                </td>
                <td class="px-5 py-4 border-b border-[#f2efed] text-[#39465a] text-[13px]">
                  <span class="text-[#748095] text-[12px]">{{ $mat['carrera']['clave'] ?? '—' }}</span>
                </td>
                <td class="px-5 py-4 border-b border-[#f2efed] text-[#39465a] text-[13px]">
                  <span class="px-2 py-1 rounded-lg text-white bg-gold-500 text-[12px] font-extrabold">{{ $mat['creditos'] }} cr</span>
                </td>
                <td class="px-5 py-4 border-b border-[#f2efed] text-[#39465a] text-[13px]">
                  <span class="text-[#748095] text-[12px]">{{ $mat['horas_teoricas'] }}h / {{ $mat['horas_practicas'] }}h</span>
                </td>
                <td class="px-5 py-4 border-b border-[#f2efed]">
                  @if($mat['activo'])
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
                    <button wire:click="abrirModalMateriaEditar({{ $mat['id_materia'] }})" title="Editar materia"
                      class="grid place-items-center w-[34px] h-[34px] rounded-[9px] border border-[#e9e5e2] text-[#687488] bg-white transition-all duration-200 hover:text-brand-600 hover:border-[#d9bcc7] hover:bg-[#fff9fb] hover:scale-110">
                      <i data-lucide="pencil" class="w-4 h-4"></i>
                    </button>
                    <button wire:click="eliminarMateria({{ $mat['id_materia'] }})" wire:confirm="¿Eliminar esta materia?"
                      title="Eliminar materia"
                      class="grid place-items-center w-[34px] h-[34px] rounded-[9px] border border-[#e9e5e2] text-[#687488] bg-white transition-all duration-200 hover:text-red-600 hover:border-red-200 hover:bg-red-50 hover:scale-110">
                      <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="p-9 text-center text-[#8993a4] text-[13px]">
                  <div class="flex flex-col items-center gap-3">
                    <i data-lucide="book-x" class="w-10 h-10 text-[#c1c6cf]"></i>
                    <span>No se encontraron materias con esa búsqueda.</span>
                  </div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </section>

    <!-- Modal Crear / Editar Materia -->
    @if($showModalMateria)
      <div class="fixed inset-0 z-[1000] grid place-items-center p-5 bg-black/55 backdrop-blur-sm" wire:click.self="$set('showModalMateria', false)">
        <div class="w-full max-w-[560px] max-h-[calc(100vh-40px)] overflow-auto rounded-[20px] bg-white shadow-2xl">
          <div class="flex items-start justify-between gap-4 px-6 pt-6 pb-4 border-b border-[#eeeae8]">
            <div>
              <h2 class="m-0 text-[#283348] text-lg">{{ $editingMateriaId ? 'Editar materia' : 'Nueva materia' }}</h2>
              <p class="mt-1 mb-0 text-[#919aaa] text-[11px]">{{ $editingMateriaId ? 'Actualiza la información de la materia seleccionada.' : 'Registra una nueva materia en el sistema TESCHA.' }}</p>
            </div>
            <button type="button" wire:click="$set('showModalMateria', false)"
              class="grid place-items-center w-[34px] h-[34px] rounded-[9px] text-[#788395] bg-[#f7f5f3] transition-colors hover:text-brand-700 hover:bg-brand-100">
              <i data-lucide="x" class="w-4 h-4"></i>
            </button>
          </div>
          <div class="p-6">
            <div class="mb-4">
              <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Carrera</label>
              <select wire:model="m_id_carrera"
                class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10 bg-white">
                <option value="">— Selecciona una carrera —</option>
                @foreach($carreras as $carr)
                  <option value="{{ $carr['id_carrera'] }}">{{ $carr['clave'] }} — {{ $carr['nombre'] }}</option>
                @endforeach
              </select>
              @error('m_id_carrera') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="mb-2">
                <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Clave</label>
                <input type="text" wire:model="m_clave" placeholder="Ej. ISIC-301"
                  class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
                @error('m_clave') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
              </div>
              <div class="mb-2">
                <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Nombre</label>
                <input type="text" wire:model="m_nombre" placeholder="Ej. Estructuras de Datos"
                  class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
                @error('m_nombre') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
              </div>
              <div class="mb-2">
                <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Créditos</label>
                <input type="number" wire:model="m_creditos" min="1" max="20" placeholder="Ej. 6"
                  class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
                @error('m_creditos') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
              </div>
              <div class="mb-2">
                <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Horas teóricas</label>
                <input type="number" wire:model="m_horas_teoricas" min="0" max="20" placeholder="Ej. 4"
                  class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
                @error('m_horas_teoricas') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
              </div>
              <div class="mb-2">
                <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Horas prácticas</label>
                <input type="number" wire:model="m_horas_practicas" min="0" max="20" placeholder="Ej. 2"
                  class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
                @error('m_horas_practicas') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
              </div>
            </div>
          </div>
          <div class="flex justify-end gap-2 px-6 pb-6 pt-2 border-t border-[#eeeae8] mt-2">
            <button type="button" wire:click="$set('showModalMateria', false)"
              class="py-2.5 px-4 rounded-[10px] border border-[#e5e1de] text-[#697487] bg-white font-bold text-xs transition-colors hover:bg-[#faf9f8]">
              Cancelar
            </button>
            <button type="button" wire:click="guardarMateria"
              class="inline-flex items-center gap-2 py-2.5 px-4 rounded-[11px] text-white bg-gradient-to-br from-brand-500 to-brand-700 shadow-[0_7px_16px_rgba(109,25,56,.18)] font-bold text-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_10px_20px_rgba(109,25,56,.23)]">
              <i data-lucide="check" class="w-4 h-4"></i>
              {{ $editingMateriaId ? 'Guardar cambios' : 'Registrar materia' }}
            </button>
          </div>
        </div>
      </div>
    @endif

  @endif

  <!-- ============================================================
       PESTAÑA: PLANES DE ESTUDIO
  ============================================================ -->
  @if($tab === 'planes')

    <!-- Estadísticas -->
    <section class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
      <div class="p-4 rounded-[15px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
        <div class="flex items-center gap-3">
          <div class="grid place-items-center w-10 h-10 rounded-xl text-brand-500 bg-brand-100">
            <i data-lucide="file-text" class="w-5 h-5"></i>
          </div>
          <div>
            <b class="block text-[#293448] text-lg">{{ $planCount }}</b>
            <span class="text-[#919aaa] text-[11px]">Total planes</span>
          </div>
        </div>
      </div>
      <div class="p-4 rounded-[15px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
        <div class="flex items-center gap-3">
          <div class="grid place-items-center w-10 h-10 rounded-xl text-emerald-600 bg-emerald-50">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
          </div>
          <div>
            <b class="block text-[#293448] text-lg">{{ collect($planes)->where('activo', 1)->count() }}</b>
            <span class="text-[#919aaa] text-[11px]">Activos</span>
          </div>
        </div>
      </div>
      <div class="p-4 rounded-[15px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
        <div class="flex items-center gap-3">
          <div class="grid place-items-center w-10 h-10 rounded-xl text-gold-500 bg-amber-50">
            <i data-lucide="x-circle" class="w-5 h-5"></i>
          </div>
          <div>
            <b class="block text-[#293448] text-lg">{{ collect($planes)->where('activo', 0)->count() }}</b>
            <span class="text-[#919aaa] text-[11px]">Inactivos</span>
          </div>
        </div>
      </div>
      <div class="p-4 rounded-[15px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
        <div class="flex items-center gap-3">
          <div class="grid place-items-center w-10 h-10 rounded-xl text-blue-600 bg-blue-50">
            <i data-lucide="calendar" class="w-5 h-5"></i>
          </div>
          <div>
            <b class="block text-[#293448] text-lg">{{ collect($planes)->whereNotNull('anio_publicacion')->count() }}</b>
            <span class="text-[#919aaa] text-[11px]">Con año publicación</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Tabla de Planes -->
    <section class="mt-4 overflow-hidden rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_8px_22px_rgba(31,38,50,.045)]">
      <div class="flex items-center justify-between px-5 py-5 border-b border-[#efedeb]">
        <div>
          <h2 class="m-0 text-[#293448] text-base">Catálogo de Planes de Estudio</h2>
          <span class="text-[#919aaa] text-[11px]">Planes de estudio registrados en el sistema TESCHA</span>
        </div>
        <span class="text-[#919aaa] text-[11px]">{{ $planCount }} registros</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full border-collapse min-w-[860px]">
          <thead>
            <tr>
              <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Plan</th>
              <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Clave</th>
              <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Carrera</th>
              <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Año</th>
              <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Estatus</th>
              <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Acciones</th>
            </tr>
          </thead>
          <tbody>
            @forelse($planes as $plan)
              <tr class="transition-colors duration-150 hover:bg-[#fcfafb]">
                <td class="px-5 py-4 border-b border-[#f2efed]">
                  <div class="flex items-center gap-3 min-w-[220px]">
                    <div class="grid place-items-center w-10 h-10 shrink-0 rounded-xl text-brand-500 bg-brand-100">
                      <i data-lucide="file-text" class="w-[19px] h-[19px]"></i>
                    </div>
                    <div>
                      <span class="block text-[#263247] font-extrabold text-[13px]">{{ $plan['nombre'] }}</span>
                      <span class="block mt-0.5 text-[#99a2b0] text-[11px]">{{ $plan['carrera']['nombre'] ?? '—' }}</span>
                    </div>
                  </div>
                </td>
                <td class="px-5 py-4 border-b border-[#f2efed] text-[#39465a] text-[13px]">
                  <span class="px-1.5 py-0.5 rounded text-white bg-brand-500 text-[10px] font-extrabold">{{ $plan['clave'] }}</span>
                </td>
                <td class="px-5 py-4 border-b border-[#f2efed] text-[#39465a] text-[13px]">
                  <span class="text-[#748095] text-[12px]">{{ $plan['carrera']['clave'] ?? '—' }}</span>
                </td>
                <td class="px-5 py-4 border-b border-[#f2efed] text-[#39465a] text-[13px]">
                  @if($plan['anio_publicacion'])
                    <span class="px-2 py-1 rounded-lg text-white bg-gold-500 text-[12px] font-extrabold">{{ $plan['anio_publicacion'] }}</span>
                  @else
                    <span class="text-[#99a2b0] text-[12px]">—</span>
                  @endif
                </td>
                <td class="px-5 py-4 border-b border-[#f2efed]">
                  @if($plan['activo'])
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
                    <button wire:click="abrirModalPlanEditar({{ $plan['id_plan_estudio'] }})" title="Editar plan"
                      class="grid place-items-center w-[34px] h-[34px] rounded-[9px] border border-[#e9e5e2] text-[#687488] bg-white transition-all duration-200 hover:text-brand-600 hover:border-[#d9bcc7] hover:bg-[#fff9fb] hover:scale-110">
                      <i data-lucide="pencil" class="w-4 h-4"></i>
                    </button>
                    <button wire:click="eliminarPlan({{ $plan['id_plan_estudio'] }})" wire:confirm="¿Eliminar este plan de estudios?"
                      title="Eliminar plan"
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
                    <i data-lucide="file-x" class="w-10 h-10 text-[#c1c6cf]"></i>
                    <span>No se encontraron planes de estudio con esa búsqueda.</span>
                  </div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </section>

    <!-- Modal Crear / Editar Plan -->
    @if($showModalPlan)
      <div class="fixed inset-0 z-[1000] grid place-items-center p-5 bg-black/55 backdrop-blur-sm" wire:click.self="$set('showModalPlan', false)">
        <div class="w-full max-w-[560px] max-h-[calc(100vh-40px)] overflow-auto rounded-[20px] bg-white shadow-2xl">
          <div class="flex items-start justify-between gap-4 px-6 pt-6 pb-4 border-b border-[#eeeae8]">
            <div>
              <h2 class="m-0 text-[#283348] text-lg">{{ $editingPlanId ? 'Editar plan de estudios' : 'Nuevo plan de estudios' }}</h2>
              <p class="mt-1 mb-0 text-[#919aaa] text-[11px]">{{ $editingPlanId ? 'Actualiza la información del plan seleccionado.' : 'Registra un nuevo plan de estudios en el sistema TESCHA.' }}</p>
            </div>
            <button type="button" wire:click="$set('showModalPlan', false)"
              class="grid place-items-center w-[34px] h-[34px] rounded-[9px] text-[#788395] bg-[#f7f5f3] transition-colors hover:text-brand-700 hover:bg-brand-100">
              <i data-lucide="x" class="w-4 h-4"></i>
            </button>
          </div>
          <div class="p-6">
            <div class="mb-4">
              <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Carrera</label>
              <select wire:model="p_id_carrera"
                class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10 bg-white">
                <option value="">— Selecciona una carrera —</option>
                @foreach($carreras as $carr)
                  <option value="{{ $carr['id_carrera'] }}">{{ $carr['clave'] }} — {{ $carr['nombre'] }}</option>
                @endforeach
              </select>
              @error('p_id_carrera') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="mb-2">
                <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Clave</label>
                <input type="text" wire:model="p_clave" placeholder="Ej. ISIC-2010-224"
                  class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
                @error('p_clave') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
              </div>
              <div class="mb-2">
                <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Nombre</label>
                <input type="text" wire:model="p_nombre" placeholder="Ej. Plan 2010"
                  class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
                @error('p_nombre') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
              </div>
              <div class="mb-2">
                <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Año de publicación <span class="text-[#919aaa] font-normal">(opcional)</span></label>
                <input type="number" wire:model="p_anio_publicacion" min="1900" max="2100" placeholder="Ej. 2010"
                  class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
                @error('p_anio_publicacion') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
              </div>
            </div>
          </div>
          <div class="flex justify-end gap-2 px-6 pb-6 pt-2 border-t border-[#eeeae8] mt-2">
            <button type="button" wire:click="$set('showModalPlan', false)"
              class="py-2.5 px-4 rounded-[10px] border border-[#e5e1de] text-[#697487] bg-white font-bold text-xs transition-colors hover:bg-[#faf9f8]">
              Cancelar
            </button>
            <button type="button" wire:click="guardarPlan"
              class="inline-flex items-center gap-2 py-2.5 px-4 rounded-[11px] text-white bg-gradient-to-br from-brand-500 to-brand-700 shadow-[0_7px_16px_rgba(109,25,56,.18)] font-bold text-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_10px_20px_rgba(109,25,56,.23)]">
              <i data-lucide="check" class="w-4 h-4"></i>
              {{ $editingPlanId ? 'Guardar cambios' : 'Registrar plan' }}
            </button>
          </div>
        </div>
      </div>
    @endif

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
