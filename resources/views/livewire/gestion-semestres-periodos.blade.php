<div>
  <!-- Header -->
  <h1 class="m-0 text-[#202b3d] text-[26px] md:text-[29px] tracking-tight">Gestión de Semestres y Periodos</h1>
  <p class="mt-1.5 mb-6 text-[#748095] text-sm">Administra los semestres por carrera y los periodos escolares del sistema.</p>

  <!-- Barra de búsqueda y botón -->
  <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 p-4 rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_8px_22px_rgba(31,38,50,.045)]">
    <div class="flex items-center gap-2.5 flex-1 sm:max-w-[430px] px-3.5 py-2.5 rounded-[11px] border border-[#e8e4e1] bg-[#faf9f8] text-[#8b95a5]">
      <i data-lucide="search" class="w-[17px]"></i>
      @if($tab === 'semestres')
        <input type="search" wire:model.live="semestreSearch" placeholder="Buscar por carrera, número o descripción..." class="w-full border-0 outline-none bg-transparent text-[#253146] text-[13px] font-medium">
      @else
        <input type="search" wire:model.live="periodoSearch" placeholder="Buscar por clave o nombre del periodo..." class="w-full border-0 outline-none bg-transparent text-[#253146] text-[13px] font-medium">
      @endif
    </div>
    @if($tab === 'semestres')
      <button type="button" wire:click="abrirModalSemestreCrear"
        class="inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-[11px] text-white bg-gradient-to-br from-brand-500 to-brand-700 shadow-[0_7px_16px_rgba(109,25,56,.18)] text-[13px] font-bold transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_10px_20px_rgba(109,25,56,.23)]">
        <i data-lucide="plus" class="w-4 h-4"></i>
        Nuevo Semestre
      </button>
    @else
      <button type="button" wire:click="abrirModalPeriodoCrear"
        class="inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-[11px] text-white bg-gradient-to-br from-brand-500 to-brand-700 shadow-[0_7px_16px_rgba(109,25,56,.18)] text-[13px] font-bold transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_10px_20px_rgba(109,25,56,.23)]">
        <i data-lucide="plus" class="w-4 h-4"></i>
        Nuevo Periodo
      </button>
    @endif
  </div>

  <!-- Mensajes flash -->
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
    <button type="button" wire:click="switchTab('semestres')"
      class="inline-flex items-center gap-2 py-2.5 px-4 rounded-[11px] text-[13px] font-bold transition-all duration-200 {{ $tab === 'semestres'
        ? 'text-white bg-gradient-to-br from-brand-500 to-brand-700 shadow-[0_7px_16px_rgba(109,25,56,.18)]'
        : 'text-[#697487] bg-white border border-[#e5e1de] hover:bg-[#faf9f8]' }}">
      <i data-lucide="calendar-range" class="w-4 h-4"></i>
      Semestres
      <span class="px-1.5 py-0.5 rounded-full text-[10px] font-extrabold {{ $tab === 'semestres' ? 'bg-white/20 text-white' : 'bg-[#f0eeec] text-[#748095]' }}">{{ $semestreCount }}</span>
    </button>
    <button type="button" wire:click="switchTab('periodos')"
      class="inline-flex items-center gap-2 py-2.5 px-4 rounded-[11px] text-[13px] font-bold transition-all duration-200 {{ $tab === 'periodos'
        ? 'text-white bg-gradient-to-br from-brand-500 to-brand-700 shadow-[0_7px_16px_rgba(109,25,56,.18)]'
        : 'text-[#697487] bg-white border border-[#e5e1de] hover:bg-[#faf9f8]' }}">
      <i data-lucide="calendar-days" class="w-4 h-4"></i>
      Periodos Escolares
      <span class="px-1.5 py-0.5 rounded-full text-[10px] font-extrabold {{ $tab === 'periodos' ? 'bg-white/20 text-white' : 'bg-[#f0eeec] text-[#748095]' }}">{{ $periodoCount }}</span>
    </button>
  </div>

  <!-- ============================================================
       PESTAÑA: SEMESTRES
  ============================================================ -->
  @if($tab === 'semestres')

    <!-- Estadísticas -->
    <section class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
      <div class="p-4 rounded-[15px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
        <div class="flex items-center gap-3">
          <div class="grid place-items-center w-10 h-10 rounded-xl text-brand-500 bg-brand-100">
            <i data-lucide="calendar-range" class="w-5 h-5"></i>
          </div>
          <div>
            <b class="block text-[#293448] text-lg">{{ $semestreCount }}</b>
            <span class="text-[#919aaa] text-[11px]">Total semestres</span>
          </div>
        </div>
      </div>
      <div class="p-4 rounded-[15px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
        <div class="flex items-center gap-3">
          <div class="grid place-items-center w-10 h-10 rounded-xl text-emerald-600 bg-emerald-50">
            <i data-lucide="landmark" class="w-5 h-5"></i>
          </div>
          <div>
            <b class="block text-[#293448] text-lg">{{ collect($semestres)->pluck('id_carrera')->unique()->count() }}</b>
            <span class="text-[#919aaa] text-[11px]">Carreras con semestres</span>
          </div>
        </div>
      </div>
      <div class="p-4 rounded-[15px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
        <div class="flex items-center gap-3">
          <div class="grid place-items-center w-10 h-10 rounded-xl text-gold-500 bg-amber-50">
            <i data-lucide="hash" class="w-5 h-5"></i>
          </div>
          <div>
            <b class="block text-[#293448] text-lg">{{ collect($semestres)->max('numero') ?? '—' }}</b>
            <span class="text-[#919aaa] text-[11px]">Último semestre</span>
          </div>
        </div>
      </div>
      <div class="p-4 rounded-[15px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
        <div class="flex items-center gap-3">
          <div class="grid place-items-center w-10 h-10 rounded-xl text-blue-600 bg-blue-50">
            <i data-lucide="file-text" class="w-5 h-5"></i>
          </div>
          <div>
            <b class="block text-[#293448] text-lg">{{ collect($semestres)->whereNotNull('descripcion')->count() }}</b>
            <span class="text-[#919aaa] text-[11px]">Con descripción</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Tabla de Semestres -->
    <section class="mt-4 overflow-hidden rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_8px_22px_rgba(31,38,50,.045)]">
      <div class="flex items-center justify-between px-5 py-5 border-b border-[#efedeb]">
        <div>
          <h2 class="m-0 text-[#293448] text-base">Catálogo de Semestres</h2>
          <span class="text-[#919aaa] text-[11px]">Semestres registrados por carrera en el sistema TESCHA</span>
        </div>
        <span class="text-[#919aaa] text-[11px]">{{ $semestreCount }} registros</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full border-collapse min-w-[860px]">
          <thead>
            <tr>
              <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Carrera</th>
              <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Semestre</th>
              <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Descripción</th>
              <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Acciones</th>
            </tr>
          </thead>
          <tbody>
            @forelse($semestres as $sem)
              <tr class="transition-colors duration-150 hover:bg-[#fcfafb]">
                <td class="px-5 py-4 border-b border-[#f2efed]">
                  <div class="flex items-center gap-3 min-w-[220px]">
                    <div class="grid place-items-center w-10 h-10 shrink-0 rounded-xl text-brand-500 bg-brand-100">
                      <i data-lucide="landmark" class="w-[19px] h-[19px]"></i>
                    </div>
                    <div>
                      <span class="block text-[#263247] font-extrabold text-[13px]">
                        {{ $sem['carrera']['nombre'] ?? '—' }}
                      </span>
                      <span class="block mt-0.5 text-[#99a2b0] text-[11px]">{{ $sem['carrera']['clave'] ?? '' }}</span>
                    </div>
                  </div>
                </td>
                <td class="px-5 py-4 border-b border-[#f2efed] text-[#39465a] text-[13px]">
                  <span class="px-2 py-1 rounded-lg text-white bg-brand-500 text-[12px] font-extrabold">{{ $sem['numero'] }}°</span>
                </td>
                <td class="px-5 py-4 border-b border-[#f2efed] text-[#39465a] text-[13px]">
                  @if($sem['descripcion'])
                    {{ $sem['descripcion'] }}
                  @else
                    <span class="text-[#99a2b0] text-[12px]">Sin descripción</span>
                  @endif
                </td>
                <td class="px-5 py-4 border-b border-[#f2efed]">
                  <div class="flex gap-1.5">
                    <button wire:click="abrirModalSemestreEditar({{ $sem['id_semestre'] }})" title="Editar semestre"
                      class="grid place-items-center w-[34px] h-[34px] rounded-[9px] border border-[#e9e5e2] text-[#687488] bg-white transition-all duration-200 hover:text-brand-600 hover:border-[#d9bcc7] hover:bg-[#fff9fb] hover:scale-110">
                      <i data-lucide="pencil" class="w-4 h-4"></i>
                    </button>
                    <button wire:click="eliminarSemestre({{ $sem['id_semestre'] }})" wire:confirm="¿Eliminar este semestre?"
                      title="Eliminar semestre"
                      class="grid place-items-center w-[34px] h-[34px] rounded-[9px] border border-[#e9e5e2] text-[#687488] bg-white transition-all duration-200 hover:text-red-600 hover:border-red-200 hover:bg-red-50 hover:scale-110">
                      <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="p-9 text-center text-[#8993a4] text-[13px]">
                  <div class="flex flex-col items-center gap-3">
                    <i data-lucide="calendar-x" class="w-10 h-10 text-[#c1c6cf]"></i>
                    <span>No se encontraron semestres con esa búsqueda.</span>
                  </div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </section>

    <!-- Modal Crear / Editar Semestre -->
    @if($showModalSemestre)
      <div class="fixed inset-0 z-[1000] grid place-items-center p-5 bg-black/55 backdrop-blur-sm" wire:click.self="$set('showModalSemestre', false)">
        <div class="w-full max-w-[560px] max-h-[calc(100vh-40px)] overflow-auto rounded-[20px] bg-white shadow-2xl">
          <div class="flex items-start justify-between gap-4 px-6 pt-6 pb-4 border-b border-[#eeeae8]">
            <div>
              <h2 class="m-0 text-[#283348] text-lg">{{ $editingSemestreId ? 'Editar semestre' : 'Nuevo semestre' }}</h2>
              <p class="mt-1 mb-0 text-[#919aaa] text-[11px]">{{ $editingSemestreId ? 'Actualiza la información del semestre seleccionado.' : 'Registra un nuevo semestre para una carrera.' }}</p>
            </div>
            <button type="button" wire:click="$set('showModalSemestre', false)"
              class="grid place-items-center w-[34px] h-[34px] rounded-[9px] text-[#788395] bg-[#f7f5f3] transition-colors hover:text-brand-700 hover:bg-brand-100">
              <i data-lucide="x" class="w-4 h-4"></i>
            </button>
          </div>
          <div class="p-6">
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
                <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Número de Semestre</label>
                <input type="number" wire:model="numero" min="1" max="9" placeholder="Ej. 5"
                  class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
                @error('numero') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
              </div>
              <div class="mb-2">
                <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Descripción <span class="text-[#919aaa] font-normal">(opcional)</span></label>
                <input type="text" wire:model="descripcion" placeholder="Ej. Optativo I"
                  class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
                @error('descripcion') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
              </div>
            </div>
          </div>
          <div class="flex justify-end gap-2 px-6 pb-6 pt-2 border-t border-[#eeeae8] mt-2">
            <button type="button" wire:click="$set('showModalSemestre', false)"
              class="py-2.5 px-4 rounded-[10px] border border-[#e5e1de] text-[#697487] bg-white font-bold text-xs transition-colors hover:bg-[#faf9f8]">
              Cancelar
            </button>
            <button type="button" wire:click="guardarSemestre"
              class="inline-flex items-center gap-2 py-2.5 px-4 rounded-[11px] text-white bg-gradient-to-br from-brand-500 to-brand-700 shadow-[0_7px_16px_rgba(109,25,56,.18)] font-bold text-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_10px_20px_rgba(109,25,56,.23)]">
              <i data-lucide="check" class="w-4 h-4"></i>
              {{ $editingSemestreId ? 'Guardar cambios' : 'Registrar semestre' }}
            </button>
          </div>
        </div>
      </div>
    @endif

  @endif

  <!-- ============================================================
       PESTAÑA: PERIODOS ESCOLARES
  ============================================================ -->
  @if($tab === 'periodos')

    <!-- Estadísticas -->
    <section class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
      <div class="p-4 rounded-[15px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
        <div class="flex items-center gap-3">
          <div class="grid place-items-center w-10 h-10 rounded-xl text-brand-500 bg-brand-100">
            <i data-lucide="calendar-days" class="w-5 h-5"></i>
          </div>
          <div>
            <b class="block text-[#293448] text-lg">{{ $periodoCount }}</b>
            <span class="text-[#919aaa] text-[11px]">Total periodos</span>
          </div>
        </div>
      </div>
      <div class="p-4 rounded-[15px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
        <div class="flex items-center gap-3">
          <div class="grid place-items-center w-10 h-10 rounded-xl text-emerald-600 bg-emerald-50">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
          </div>
          <div>
            <b class="block text-[#293448] text-lg">{{ collect($periodos)->where('activo', 1)->count() }}</b>
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
            <b class="block text-[#293448] text-lg">{{ collect($periodos)->where('activo', 0)->count() }}</b>
            <span class="text-[#919aaa] text-[11px]">Inactivos</span>
          </div>
        </div>
      </div>
      <div class="p-4 rounded-[15px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
        <div class="flex items-center gap-3">
          <div class="grid place-items-center w-10 h-10 rounded-xl text-blue-600 bg-blue-50">
            <i data-lucide="calendar-check" class="w-5 h-5"></i>
          </div>
          <div>
            <b class="block text-[#293448] text-lg">{{ collect($periodos)->whereNotNull('fecha_inicio')->count() }}</b>
            <span class="text-[#919aaa] text-[11px]">Con fechas definidas</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Tabla de Periodos -->
    <section class="mt-4 overflow-hidden rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_8px_22px_rgba(31,38,50,.045)]">
      <div class="flex items-center justify-between px-5 py-5 border-b border-[#efedeb]">
        <div>
          <h2 class="m-0 text-[#293448] text-base">Catálogo de Periodos Escolares</h2>
          <span class="text-[#919aaa] text-[11px]">Ciclos escolares registrados en el sistema TESCHA</span>
        </div>
        <span class="text-[#919aaa] text-[11px]">{{ $periodoCount }} registros</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full border-collapse min-w-[860px]">
          <thead>
            <tr>
              <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Periodo</th>
              <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Clave</th>
              <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Fechas</th>
              <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Estatus</th>
              <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Acciones</th>
            </tr>
          </thead>
          <tbody>
            @forelse($periodos as $per)
              <tr class="transition-colors duration-150 hover:bg-[#fcfafb]">
                <td class="px-5 py-4 border-b border-[#f2efed]">
                  <div class="flex items-center gap-3 min-w-[220px]">
                    <div class="grid place-items-center w-10 h-10 shrink-0 rounded-xl text-brand-500 bg-brand-100">
                      <i data-lucide="calendar-days" class="w-[19px] h-[19px]"></i>
                    </div>
                    <div>
                      <span class="block text-[#263247] font-extrabold text-[13px]">
                        {{ $per['nombre'] }}
                      </span>
                      <span class="block mt-0.5 text-[#99a2b0] text-[11px]">Periodo escolar</span>
                    </div>
                  </div>
                </td>
                <td class="px-5 py-4 border-b border-[#f2efed] text-[#39465a] text-[13px]">
                  <span class="px-1.5 py-0.5 rounded text-white bg-brand-500 text-[10px] font-extrabold">{{ $per['clave'] }}</span>
                </td>
                <td class="px-5 py-4 border-b border-[#f2efed] text-[#39465a] text-[13px]">
                  @if($per['fecha_inicio'] && $per['fecha_fin'])
                    {{ \Carbon\Carbon::parse($per['fecha_inicio'])->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($per['fecha_fin'])->format('d/m/Y') }}
                  @elseif($per['fecha_inicio'])
                    Desde {{ \Carbon\Carbon::parse($per['fecha_inicio'])->format('d/m/Y') }}
                  @else
                    <span class="text-[#99a2b0] text-[12px]">Sin fechas definidas</span>
                  @endif
                </td>
                <td class="px-5 py-4 border-b border-[#f2efed]">
                  @if($per['activo'])
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
                    <button wire:click="abrirModalPeriodoEditar({{ $per['id_periodo'] }})" title="Editar periodo"
                      class="grid place-items-center w-[34px] h-[34px] rounded-[9px] border border-[#e9e5e2] text-[#687488] bg-white transition-all duration-200 hover:text-brand-600 hover:border-[#d9bcc7] hover:bg-[#fff9fb] hover:scale-110">
                      <i data-lucide="pencil" class="w-4 h-4"></i>
                    </button>
                    <button wire:click="eliminarPeriodo({{ $per['id_periodo'] }})" wire:confirm="¿Eliminar este periodo?"
                      title="Eliminar periodo"
                      class="grid place-items-center w-[34px] h-[34px] rounded-[9px] border border-[#e9e5e2] text-[#687488] bg-white transition-all duration-200 hover:text-red-600 hover:border-red-200 hover:bg-red-50 hover:scale-110">
                      <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="p-9 text-center text-[#8993a4] text-[13px]">
                  <div class="flex flex-col items-center gap-3">
                    <i data-lucide="calendar-x" class="w-10 h-10 text-[#c1c6cf]"></i>
                    <span>No se encontraron periodos con esa búsqueda.</span>
                  </div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </section>

    <!-- Modal Crear / Editar Periodo -->
    @if($showModalPeriodo)
      <div class="fixed inset-0 z-[1000] grid place-items-center p-5 bg-black/55 backdrop-blur-sm" wire:click.self="$set('showModalPeriodo', false)">
        <div class="w-full max-w-[560px] max-h-[calc(100vh-40px)] overflow-auto rounded-[20px] bg-white shadow-2xl">
          <div class="flex items-start justify-between gap-4 px-6 pt-6 pb-4 border-b border-[#eeeae8]">
            <div>
              <h2 class="m-0 text-[#283348] text-lg">{{ $editingPeriodoId ? 'Editar periodo' : 'Nuevo periodo' }}</h2>
              <p class="mt-1 mb-0 text-[#919aaa] text-[11px]">{{ $editingPeriodoId ? 'Actualiza la información del periodo seleccionado.' : 'Registra un nuevo periodo escolar en el sistema TESCHA.' }}</p>
            </div>
            <button type="button" wire:click="$set('showModalPeriodo', false)"
              class="grid place-items-center w-[34px] h-[34px] rounded-[9px] text-[#788395] bg-[#f7f5f3] transition-colors hover:text-brand-700 hover:bg-brand-100">
              <i data-lucide="x" class="w-4 h-4"></i>
            </button>
          </div>
          <div class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="mb-2">
                <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Clave</label>
                <input type="text" wire:model="clave" placeholder="Ej. 2026-1"
                  class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
                @error('clave') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
              </div>
              <div class="mb-2">
                <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Nombre</label>
                <input type="text" wire:model="nombre" placeholder="Ej. Febrero - Junio 2026"
                  class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
                @error('nombre') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
              </div>
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
            <button type="button" wire:click="$set('showModalPeriodo', false)"
              class="py-2.5 px-4 rounded-[10px] border border-[#e5e1de] text-[#697487] bg-white font-bold text-xs transition-colors hover:bg-[#faf9f8]">
              Cancelar
            </button>
            <button type="button" wire:click="guardarPeriodo"
              class="inline-flex items-center gap-2 py-2.5 px-4 rounded-[11px] text-white bg-gradient-to-br from-brand-500 to-brand-700 shadow-[0_7px_16px_rgba(109,25,56,.18)] font-bold text-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_10px_20px_rgba(109,25,56,.23)]">
              <i data-lucide="check" class="w-4 h-4"></i>
              {{ $editingPeriodoId ? 'Guardar cambios' : 'Registrar periodo' }}
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
