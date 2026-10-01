@extends('panels.control-escolar.layout')

@section('miga-de-pan', 'Panel de Control Escolar')

@section('content')
    {{-- ===== INICIO DEL PANEL DE CONTROL ESCOLAR ===== --}}
    <h1 class="m-0 text-[#202b3d] text-[26px] md:text-[29px] tracking-tight">Panel de Control Escolar</h1>
    <p class="mt-1.5 mb-6 text-[#748095] text-sm">
        Bienvenido, {{ Auth::user()->name ?? 'Control Escolar' }} · {{ \Carbon\Carbon::now()->isoFormat('D [de] MMMM [de] YYYY') }}
    </p>

    {{-- Banner: periodo activo SOLO si periodo_escolars.activo = 1 --}}
    <section class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 p-5 rounded-[17px] text-white bg-gradient-to-r from-brand-800 to-brand-700 shadow-[0_12px_25px_rgba(92,19,48,.16)]">
        <div class="flex items-center gap-4">
            <div class="grid place-items-center w-12 h-12 rounded-[13px] text-gold-300 bg-white/15">
                <i data-lucide="landmark" class="w-[23px]"></i>
            </div>
            <div>
                <span class="text-white/65 text-[11px] font-extrabold tracking-[.08em]">CONTROL ESCOLAR · TESCHA</span>
                <strong class="block mt-1 text-lg md:text-xl tracking-tight">Gestión escolar del ciclo escolar</strong>
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

    {{-- Contadores REALES de los módulos consultables por el rol --}}
    <section class="grid grid-cols-2 lg:grid-cols-3 gap-4 mt-5">
        @php
            $tarjetas = [
                ['icono' => 'users-round',    'color' => 'text-brand-500 bg-brand-100',    'n' => $totalAlumnos,       'label' => 'Alumnos'],
                ['icono' => 'file-text',      'color' => 'text-blue-600 bg-blue-50',       'n' => $totalInscripciones, 'label' => 'Inscripciones'],
                ['icono' => 'grid-2x2',       'color' => 'text-emerald-600 bg-emerald-50', 'n' => $totalGrupos,        'label' => 'Grupos'],
                ['icono' => 'clipboard-list', 'color' => 'text-brand-500 bg-brand-100',    'n' => $totalCalificaciones,'label' => 'Calificaciones'],
                ['icono' => 'user-cog',       'color' => 'text-gold-500 bg-amber-50',      'n' => $totalDocentes,      'label' => 'Docentes'],
                ['icono' => 'user-round-cog', 'color' => 'text-violet-600 bg-violet-50',   'n' => $totalUsuarios,      'label' => 'Cuentas de usuario'],
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
        <h2 class="m-0 text-[#293448] text-[17px]">Gestión Escolar</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 mt-4">
            @php
                $gestion = [
                    ['ruta' => 'panel.control-escolar.alumnos',       'icono' => 'users-round',    'color' => 'text-brand-500 bg-brand-100',    'titulo' => 'Alumnos',               'desc' => 'Alta y consulta'],
                    ['ruta' => 'panel.control-escolar.inscripciones', 'icono' => 'file-text',      'color' => 'text-blue-600 bg-blue-50',       'titulo' => 'Inscripciones',         'desc' => 'Módulo en preparación'],
                    ['ruta' => 'panel.control-escolar.grupos',        'icono' => 'grid-2x2',       'color' => 'text-emerald-600 bg-emerald-50', 'titulo' => 'Grupos y Asignaciones', 'desc' => 'Grupos y docentes'],
                    ['ruta' => 'panel.control-escolar.calificaciones','icono' => 'clipboard-list', 'color' => 'text-brand-500 bg-brand-100',    'titulo' => 'Calificaciones y Actas','desc' => 'Módulo en preparación'],
                    ['ruta' => 'panel.control-escolar.historial',     'icono' => 'scroll-text',    'color' => 'text-gold-500 bg-amber-50',      'titulo' => 'Historial Académico',   'desc' => 'Módulo en preparación'],
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
        <h2 class="m-0 text-[#293448] text-[17px]">Catálogos</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mt-4">
            @php
                $catalogos = [
                    ['ruta' => 'panel.control-escolar.docentes',           'icono' => 'user-cog',           'color' => 'text-brand-500 bg-brand-100',    'titulo' => 'Docentes',            'desc' => 'Personal docente'],
                    ['ruta' => 'panel.control-escolar.jefes-carrera',      'icono' => 'briefcase-business', 'color' => 'text-gold-500 bg-amber-50',      'titulo' => 'Jefes de Carrera',    'desc' => 'Responsables de carrera'],
                    ['ruta' => 'panel.control-escolar.carreras',           'icono' => 'landmark',           'color' => 'text-blue-600 bg-blue-50',       'titulo' => 'Carreras',            'desc' => 'Oferta académica'],
                    ['ruta' => 'panel.control-escolar.semestres-periodos', 'icono' => 'calendar-days',      'color' => 'text-emerald-600 bg-emerald-50', 'titulo' => 'Semestres / Periodos', 'desc' => 'Calendario escolar'],
                ];
            @endphp

            @foreach ($catalogos as $item)
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
        <h2 class="m-0 text-[#293448] text-[17px]">Usuarios</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-4">
            <a href="{{ route('panel.control-escolar.gestion-usuarios') }}"
               class="min-h-[110px] p-4 rounded-[13px] border border-[#f0eeec] bg-white text-[#253146] transition-all duration-200 hover:border-[#ddc3cd] hover:bg-[#fffafa] hover:-translate-y-1 hover:shadow-lg">
                <i class="grid place-items-center w-[42px] h-[42px] mb-3 rounded-xl text-violet-600 bg-violet-50" data-lucide="user-round-cog"></i>
                <b class="block text-[13px]">Gestión de Usuarios</b>
                <span class="block mt-1 text-[#929cad] text-[11px]">Consulta de cuentas del sistema</span>
            </a>
        </div>
    </section>
@endsection
