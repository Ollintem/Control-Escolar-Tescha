@extends('panels.jefe-carrera.layout')

@section('miga-de-pan', 'Panel de Jefe de Carrera')

@section('content')
    {{-- ===== INICIO DEL PANEL JEFE DE CARRERA ===== --}}
    <h1 class="m-0 text-[#202b3d] text-[26px] md:text-[29px] tracking-tight">Panel de Jefe de Carrera</h1>
    <p class="mt-1.5 mb-6 text-[#748095] text-sm">
        Bienvenido, {{ Auth::user()->name ?? 'Jefe de Carrera' }} · {{ \Carbon\Carbon::now()->isoFormat('D [de] MMMM [de] YYYY') }}
    </p>

    {{-- Banner: carrera del jefe (dato real) y periodo activo SOLO si existe --}}
    <section class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 p-5 rounded-[17px] text-white bg-gradient-to-r from-brand-800 to-brand-700 shadow-[0_12px_25px_rgba(92,19,48,.16)]">
        <div class="flex items-center gap-4">
            <div class="grid place-items-center w-12 h-12 rounded-[13px] text-gold-300 bg-white/15">
                <i data-lucide="landmark" class="w-[23px]"></i>
            </div>
            <div>
                <span class="text-white/65 text-[11px] font-extrabold tracking-[.08em]">CARRERA A cargo</span>
                <strong class="block mt-1 text-lg md:text-xl tracking-tight">
                    @if ($carrera)
                        {{ $carrera->nombre }} <span class="text-white/60">({{ $carrera->clave }})</span>
                    @else
                        Sin carrera asignada
                    @endif
                </strong>
            </div>
        </div>

        <div class="hidden sm:flex items-center gap-3.5">
            @if ($periodoActivo)
                <div class="min-w-[130px] py-2 px-3.5 rounded-xl bg-white/15 text-center">
                    <b class="text-base">{{ $periodoActivo->clave }}</b>
                    <span class="block mt-0.5 text-white/65 text-[11px]">Periodo Activo</span>
                </div>
            @endif
        </div>
    </section>

    {{-- Vínculo users.id_jefe_carrera: si es NULL no se consulta nada. --}}
    @if ($idCarrera === null)
        <section class="mt-5 p-4 rounded-[17px] border border-[#f0e2c9] bg-[#fdf8ee] text-[#7a5a17] text-[13px] leading-6">
            <b class="block text-[#5f4410]">Información de tu cuenta</b>
            Tu cuenta aún no tiene vinculado un registro de jefe de carrera (relación
            <code class="px-1 rounded bg-white/70">usuarios → jefe de carrera → carrera</code>), por lo que no se puede
            determinar qué carrera consultar. Contacta al Administrador para completar tu vinculación.
        </section>
    @elseif ($jefe)
        <section class="mt-5 p-4 rounded-[17px] border border-[#ece9e6] bg-white text-[#748095] text-[13px] leading-6 shadow-[0_5px_13px_rgba(31,38,50,.04)]">
            <b class="block text-[#293448]">Datos del responsable</b>
            {{ $jefe->nombre }} {{ $jefe->apellido_paterno }} {{ $jefe->apellido_materno }}
            · No. empleado <b class="text-[#293448]">{{ $jefe->no_empleado }}</b>
            · {{ $jefe->email }}
        </section>
    @endif

    {{-- Contadores REALES de MI carrera (0 si no hay datos; nunca se inventan cifras) --}}
    <section class="grid grid-cols-2 lg:grid-cols-3 gap-4 mt-5">
        @php
            $tarjetas = [
                ['icono' => 'book-open',          'color' => 'text-brand-500 bg-brand-100', 'n' => $totalMaterias,       'label' => 'Materias de la carrera'],
                ['icono' => 'user-cog',           'color' => 'text-gold-500 bg-amber-50',   'n' => $totalDocentes,       'label' => 'Docentes en la carrera'],
                ['icono' => 'grid-2x2',           'color' => 'text-blue-600 bg-blue-50',    'n' => $totalGrupos,         'label' => 'Grupos'],
                ['icono' => 'users-round',        'color' => 'text-emerald-600 bg-emerald-50', 'n' => $totalAlumnos,     'label' => 'Alumnos'],
                ['icono' => 'clipboard-list',     'color' => 'text-brand-500 bg-brand-100', 'n' => $totalCalificaciones, 'label' => 'Calificaciones'],
                ['icono' => 'scroll-text',        'color' => 'text-gold-500 bg-amber-50',   'n' => $totalHistorial,      'label' => 'Historial académico'],
            ];
        @endphp

        @foreach ($tarjetas as $tarjeta)
            <article class="p-5 rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
                <div class="flex items-center gap-3">
                    <div class="grid place-items-center w-[42px] h-[42px] rounded-xl {{ $tarjeta['color'] }}">
                        <i data-lucide="{{ $tarjeta['icono'] }}" class="w-[20px]"></i>
                    </div>
                    <div>
                        <b class="block text-[#202b3d] text-2xl tracking-tight">{{ $tarjeta['n'] }}</b>
                        <span class="block text-[#929cad] text-[11px]">{{ $tarjeta['label'] }}</span>
                    </div>
                </div>
            </article>
        @endforeach
    </section>

    {{-- Accesos directos: mismo agrupamiento del sidebar --}}
    <section class="mt-5 p-5 rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
        <h2 class="m-0 text-[#293448] text-[17px]">Gestión Académica</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mt-4">
            @php
                $gestion = [
                    ['ruta' => 'panel.jefe-carrera.materias-plan', 'icono' => 'book-open',    'color' => 'text-brand-500 bg-brand-100', 'titulo' => 'Materias y Plan de Estudios', 'desc' => 'Retícula de tu carrera'],
                    ['ruta' => 'panel.jefe-carrera.docentes',      'icono' => 'user-cog',     'color' => 'text-gold-500 bg-amber-50',   'titulo' => 'Docentes',                     'desc' => 'Personal de tu carrera'],
                    ['ruta' => 'panel.jefe-carrera.grupos',        'icono' => 'grid-2x2',     'color' => 'text-blue-600 bg-blue-50',    'titulo' => 'Grupos',                       'desc' => 'Grupos de tus materias'],
                    ['ruta' => 'panel.jefe-carrera.alumnos',       'icono' => 'users-round',  'color' => 'text-emerald-600 bg-emerald-50', 'titulo' => 'Alumnos',                   'desc' => 'Inscritos en la carrera'],
                ];
            @endphp

            @foreach ($gestion as $item)
                <a href="{{ route($item['ruta']) }}"
                   class="min-h-[110px] p-4 rounded-[13px] border border-[#f0eeec] bg-white text-[#253146] transition-all duration-200 hover:border-[#ddc3cd] hover:bg-[#fffafa] hover:-translate-y-1 hover:shadow-lg">
                    <i class="grid place-items-center w-[42px] h-[42px] mb-3 rounded-xl {{ $item['color'] }}" data-lucide="{{ $item['icono'] }}"></i>
                    <b class="block text-[13px]">{{ $item['titulo'] }}</b>
                    <span class="block mt-1 text-[#929cad] text-[11px]">{{ $item['desc'] }}</span>
                </a>
            @endforeach
        </div>
    </section>

    <section class="mt-5 p-5 rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
        <h2 class="m-0 text-[#293448] text-[17px]">Seguimiento</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-4">
            <a href="{{ route('panel.jefe-carrera.calificaciones') }}"
               class="min-h-[110px] p-4 rounded-[13px] border border-[#f0eeec] bg-white text-[#253146] transition-all duration-200 hover:border-[#ddc3cd] hover:bg-[#fffafa] hover:-translate-y-1 hover:shadow-lg">
                <i class="grid place-items-center w-[42px] h-[42px] mb-3 rounded-xl text-brand-500 bg-brand-100" data-lucide="clipboard-list"></i>
                <b class="block text-[13px]">Calificaciones</b>
                <span class="block mt-1 text-[#929cad] text-[11px]">Parciales de los grupos de tu carrera</span>
            </a>

            <a href="{{ route('panel.jefe-carrera.historial') }}"
               class="min-h-[110px] p-4 rounded-[13px] border border-[#f0eeec] bg-white text-[#253146] transition-all duration-200 hover:border-[#ddc3cd] hover:bg-[#fffafa] hover:-translate-y-1 hover:shadow-lg">
                <i class="grid place-items-center w-[42px] h-[42px] mb-3 rounded-xl text-emerald-600 bg-emerald-50" data-lucide="scroll-text"></i>
                <b class="block text-[13px]">Historial Académico</b>
                <span class="block mt-1 text-[#98a2b2] text-[11px]">Asignaturas cursadas de tus alumnos</span>
            </a>
        </div>
    </section>

    {{-- Estado vacío general: solo cuando realmente no hay nada que mostrar --}}
    @if ($idCarrera !== null && $totalMaterias + $totalDocentes + $totalGrupos + $totalAlumnos + $totalCalificaciones + $totalHistorial === 0)
        <section class="mt-5 p-8 rounded-[17px] border border-[#ece9e6] bg-white text-center shadow-[0_5px_13px_rgba(31,38,50,.04)]">
            <div class="grid place-items-center w-[52px] h-[52px] mx-auto mb-4 rounded-2xl text-[#98a2b2] bg-cream">
                <i data-lucide="landmark" class="w-[24px]"></i>
            </div>
            <p class="m-0 text-[#748095] text-[14px]">No hay información disponible para tu carrera.</p>
        </section>
    @endif
@endsection
