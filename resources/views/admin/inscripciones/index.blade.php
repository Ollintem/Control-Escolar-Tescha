<div>
  <h1 class="m-0 text-[#202b3d] text-[26px] md:text-[29px] tracking-tight">Gestión de Inscripciones</h1>
  <p class="mt-1.5 mb-6 text-[#748095] text-sm">Consulta inscripciones históricas y registra alumnos en grupos de periodos activos.</p>

  @if(session('success'))
    <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-[11px] text-sm font-semibold shadow-sm flex items-center gap-2">
      <i data-lucide="check-circle" class="w-4 h-4 text-emerald-500"></i>
      {{ session('success') }}
    </div>
  @endif

  @if(session('error'))
    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-[11px] text-sm font-semibold shadow-sm flex items-center gap-2">
      <i data-lucide="alert-circle" class="w-4 h-4 text-red-500"></i>
      {{ session('error') }}
    </div>
  @endif

  @can('Inscripciones.crear')
    @php
      $alumnoSeleccionado = collect($alumnosDisponibles)->firstWhere('id_alumno', (int) $alumnoId);
    @endphp

    <section class="mb-5 p-5 rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
      <div class="mb-4">
        <h2 class="m-0 text-[#293448] text-base">Registrar alumno en un grupo</h2>
        <p class="mt-1 mb-0 text-[#919aaa] text-xs">Solo se muestran grupos activos de su carrera y de periodos actualmente activos.</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label for="busqueda-alumno" class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Buscar alumno</label>
          <input id="busqueda-alumno" type="search" wire:model.live.debounce.300ms="busquedaAlumno"
            placeholder="Número de control o nombre"
            class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
        </div>

        <div>
          <label for="alumno-id" class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Alumno</label>
          <select id="alumno-id" wire:model.live="alumnoId"
            class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10 bg-white">
            <option value="">— Selecciona un alumno activo —</option>
            @foreach($alumnosDisponibles as $alumno)
              <option value="{{ $alumno['id_alumno'] }}">
                {{ $alumno['no_control'] }} — {{ $alumno['nombre'] }} {{ $alumno['apellido_paterno'] }} {{ $alumno['apellido_materno'] }}
              </option>
            @endforeach
          </select>
          @error('alumnoId') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
          @if($alumnoSeleccionado)
            <span class="block mt-1.5 text-[#748095] text-[11px]">
              Carrera: {{ $alumnoSeleccionado['carrera']['nombre'] ?? 'No disponible' }}
            </span>
          @endif
        </div>

        <div>
          <label for="periodo-id" class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Periodo escolar</label>
          <select id="periodo-id" wire:model.live="periodoId"
            class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10 bg-white">
            <option value="">— Selecciona un periodo activo —</option>
            @foreach($periodosActivos as $periodo)
              <option value="{{ $periodo['id_periodo'] }}">{{ $periodo['clave'] }} — {{ $periodo['nombre'] }}</option>
            @endforeach
          </select>
          @error('periodoId') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div>
          <label for="grupo-id" class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Grupo disponible</label>
          <select id="grupo-id" wire:model="grupoId"
            class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10 bg-white">
            <option value="">— Selecciona un grupo —</option>
            @foreach($gruposDisponibles as $grupo)
              <option value="{{ $grupo['id_grupo'] }}" {{ ! $grupo['disponible'] ? 'disabled' : '' }}>
                {{ $grupo['materia_clave'] }} — {{ $grupo['materia'] }} · Grupo {{ $grupo['grupo'] }} ·
                {{ $grupo['id_docente'] === null ? 'Sin docente asignado' : trim($grupo['docente_nombre'].' '.$grupo['docente_apellido_paterno'].' '.$grupo['docente_apellido_materno']) }} ·
                {{ $grupo['ocupados'] }}/{{ $grupo['cupo_maximo'] }} lugares
                {{ $grupo['disponible'] ? '' : '· CUPO LLENO' }}
              </option>
            @endforeach
          </select>
          @error('grupoId') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
          @if($alumnoId !== '' && $periodoId !== '' && $gruposDisponibles === [])
            <span class="block mt-1.5 text-[#919aaa] text-[11px]">No hay grupos disponibles para la carrera del alumno en este periodo.</span>
          @endif
        </div>
      </div>

      <div class="flex justify-end mt-5">
        <button type="button" wire:click="guardar" wire:loading.attr="disabled"
          class="inline-flex items-center gap-2 py-2.5 px-4 rounded-[11px] text-white bg-gradient-to-br from-brand-500 to-brand-700 shadow-[0_7px_16px_rgba(109,25,56,.18)] text-[13px] font-bold transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_10px_20px_rgba(109,25,56,.23)] disabled:opacity-50">
          <i data-lucide="user-round-plus" class="w-4 h-4"></i>
          Registrar inscripción ordinaria
        </button>
      </div>
    </section>
  @endcan

  <section class="mb-4 flex flex-col md:flex-row gap-3 p-4 rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
    <div class="flex-1">
      <label for="busqueda-inscripciones" class="sr-only">Buscar inscripciones por alumno</label>
      <input id="busqueda-inscripciones" type="search" wire:model.live.debounce.300ms="busqueda"
        placeholder="Buscar por número de control o nombre del alumno"
        class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
    </div>
    <div class="w-full md:w-64">
      <label for="filtro-periodo" class="sr-only">Filtrar por periodo</label>
      <select id="filtro-periodo" wire:model.live="filtroPeriodo"
        class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10 bg-white">
        <option value="">Todos los periodos</option>
        @foreach($periodosFiltro as $periodo)
          <option value="{{ $periodo['id_periodo'] }}">{{ $periodo['clave'] }} — {{ $periodo['nombre'] }}</option>
        @endforeach
      </select>
    </div>
    <div class="w-full md:w-56">
      <label for="filtro-grupo" class="sr-only">Filtrar por grupo</label>
      <input id="filtro-grupo" type="search" wire:model.live.debounce.300ms="filtroGrupo"
        placeholder="Grupo o materia"
        class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
    </div>
  </section>

  <section class="overflow-hidden rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_8px_22px_rgba(31,38,50,.045)]">
    <div class="flex items-center justify-between px-5 py-5 border-b border-[#efedeb]">
      <div>
        <h2 class="m-0 text-[#293448] text-base">Inscripciones registradas</h2>
        <span class="text-[#919aaa] text-[11px]">Se incluyen periodos históricos para consulta.</span>
      </div>
      <span class="text-[#919aaa] text-[11px]">{{ $inscripciones->total() }} registros</span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full border-collapse min-w-[1080px]">
        <thead>
          <tr>
            <th class="px-4 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.06em]">Alumno</th>
            <th class="px-4 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.06em]">Carrera</th>
            <th class="px-4 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.06em]">Materia</th>
            <th class="px-4 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.06em]">Grupo</th>
            <th class="px-4 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.06em]">Docente titular</th>
            <th class="px-4 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.06em]">Periodo</th>
            <th class="px-4 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.06em]">Fecha</th>
            <th class="px-4 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.06em]">Estatus</th>
          </tr>
        </thead>
        <tbody class="text-[#354156] text-[12px]">
          @forelse($inscripciones as $inscripcion)
            <tr class="border-t border-cream hover:bg-[#fcfafb]">
              <td class="px-4 py-3.5">
                <b class="block">{{ $inscripcion->alumno?->no_control ?? '—' }}</b>
                <span class="block mt-0.5 text-[#748095]">
                  {{ trim(($inscripcion->alumno?->nombre ?? '').' '.($inscripcion->alumno?->apellido_paterno ?? '').' '.($inscripcion->alumno?->apellido_materno ?? '')) ?: 'Alumno no disponible' }}
                </span>
              </td>
              <td class="px-4 py-3.5">{{ $inscripcion->alumno?->carrera?->nombre ?? '—' }}</td>
              <td class="px-4 py-3.5">
                <b>{{ $inscripcion->grupo?->materia?->clave ?? '—' }}</b>
                <span class="block mt-0.5 text-[#748095]">{{ $inscripcion->grupo?->materia?->nombre ?? 'Materia no disponible' }}</span>
              </td>
              <td class="px-4 py-3.5">{{ $inscripcion->grupo?->nombre ?? '—' }}</td>
              <td class="px-4 py-3.5">
                @if($inscripcion->grupo?->docente)
                  {{ trim($inscripcion->grupo->docente->nombre.' '.$inscripcion->grupo->docente->apellido_paterno.' '.($inscripcion->grupo->docente->apellido_materno ?? '')) }}
                @else
                  <span class="text-[#919aaa]">Sin docente asignado</span>
                @endif
              </td>
              <td class="px-4 py-3.5">
                <b>{{ $inscripcion->grupo?->periodo?->clave ?? '—' }}</b>
                <span class="block mt-0.5 text-[#748095]">{{ $inscripcion->grupo?->periodo?->nombre ?? 'Periodo no disponible' }}</span>
              </td>
              <td class="px-4 py-3.5">{{ $inscripcion->fecha_inscripcion ?? '—' }}</td>
              <td class="px-4 py-3.5">{{ $inscripcion->estatus }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="p-9 text-center text-[#8993a4] text-[13px]">No se encontraron inscripciones.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($inscripciones->hasPages())
      <div class="px-5 py-4 border-t border-[#efedeb]">
        {{ $inscripciones->links() }}
      </div>
    @endif
  </section>
</div>
