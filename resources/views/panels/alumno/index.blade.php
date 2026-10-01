@extends('panels.alumno.layout')

@section('miga-de-pan', 'Panel del Alumno')

@section('content')
    {{-- ===== INICIO DEL PANEL DEL ALUMNO ===== --}}
    <h1 class="m-0 text-[#202b3d] text-[26px] md:text-[29px] tracking-tight">Panel del Alumno</h1>
    <p class="mt-1.5 mb-6 text-[#748095] text-sm">
        Bienvenido, {{ Auth::user()->name ?? 'Alumno' }} · {{ \Carbon\Carbon::now()->isoFormat('D [de] MMMM [de] YYYY') }}
    </p>

    {{-- Banner: SOLO muestra el periodo activo si realmente existe en periodo_escolars --}}
    <section class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 p-5 rounded-[17px] text-white bg-gradient-to-r from-brand-800 to-brand-700 shadow-[0_12px_25px_rgba(92,19,48,.16)]">
        <div class="flex items-center gap-4">
            <div class="grid place-items-center w-12 h-12 rounded-[13px] text-gold-300 bg-white/15">
                <i data-lucide="graduation-cap" class="w-[23px]"></i>
            </div>
            <div>
                <span class="text-white/65 text-[11px] font-extrabold tracking-[.08em]">ESPACIO ACADÉMICO</span>
                <strong class="block mt-1 text-lg md:text-xl tracking-tight">Información escolar de tu ciclo</strong>
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

    {{-- Vínculo users.id_alumno: si es NULL no se consulta nada académico. --}}
    @if ($idAlumno === null)
        <section class="mt-5 p-4 rounded-[17px] border border-[#f0e2c9] bg-[#fdf8ee] text-[#7a5a17] text-[13px] leading-6">
            <b class="block text-[#5f4410]">Información de tu cuenta</b>
            Tu cuenta aún no tiene vinculado un registro académico (relación
            <code class="px-1 rounded bg-white/70">usuarios → alumno</code>). Por eso no se muestran calificaciones ni
            historial. Contacta a Control Escolar para completar tu vinculación.
        </section>
    @endif

    {{-- Contadores REALES (0 si la base no tiene datos; nunca se inventan cifras) --}}
    <section class="grid grid-cols-2 lg:grid-cols-3 gap-4 mt-5">
        <article class="p-5 rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
            <div class="flex items-center gap-3">
                <div class="grid place-items-center w-[42px] h-[42px] rounded-xl text-brand-500 bg-brand-100">
                    <i data-lucide="book-open-check" class="w-[20px]"></i>
                </div>
                <div>
                    <b class="block text-[#202b3d] text-2xl tracking-tight">{{ $totalInscripciones }}</b>
                    <span class="block text-[#929cad] text-[11px]">Materias inscritas</span>
                </div>
            </div>
        </article>

        <article class="p-5 rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
            <div class="flex items-center gap-3">
                <div class="grid place-items-center w-[42px] h-[42px] rounded-xl text-gold-500 bg-amber-50">
                    <i data-lucide="clipboard-list" class="w-[20px]"></i>
                </div>
                <div>
                    <b class="block text-[#202b3d] text-2xl tracking-tight">{{ $totalCalificaciones }}</b>
                    <span class="block text-[#929cad] text-[11px]">Calificaciones registradas</span>
                </div>
            </div>
        </article>

        <article class="p-5 rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
            <div class="flex items-center gap-3">
                <div class="grid place-items-center w-[42px] h-[42px] rounded-xl text-emerald-600 bg-emerald-50">
                    <i data-lucide="scroll-text" class="w-[20px]"></i>
                </div>
                <div>
                    <b class="block text-[#202b3d] text-2xl tracking-tight">{{ $totalHistorial }}</b>
                    <span class="block text-[#929cad] text-[11px]">Asignaturas acreditadas</span>
                </div>
            </div>
        </article>
    </section>

    {{-- Accesos directos a MI INFORMACIÓN (sin módulos administrativos) --}}
    <section class="mt-5 p-5 rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
        <h2 class="m-0 text-[#293448] text-[17px]">Mi información</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-4">
            <a href="{{ route('panel.alumno.calificaciones') }}"
               class="min-h-[110px] p-4 rounded-[13px] border border-[#f0eeec] bg-white text-[#253146] transition-all duration-200 hover:border-[#ddc3cd] hover:bg-[#fffafa] hover:-translate-y-1 hover:shadow-lg">
                <i class="grid place-items-center w-[42px] h-[42px] mb-3 rounded-xl text-brand-500 bg-brand-100" data-lucide="clipboard-list"></i>
                <b class="block text-[13px]">Mis Calificaciones</b>
                <span class="block mt-1 text-[#929cad] text-[11px]">Parciales de tus materias inscritas</span>
            </a>

            <a href="{{ route('panel.alumno.historial') }}"
               class="min-h-[110px] p-4 rounded-[13px] border border-[#f0eeec] bg-white text-[#253146] transition-all duration-200 hover:border-[#ddc3cd] hover:bg-[#fffafa] hover:-translate-y-1 hover:shadow-lg">
                <i class="grid place-items-center w-[42px] h-[42px] mb-3 rounded-xl text-emerald-600 bg-emerald-50" data-lucide="scroll-text"></i>
                <b class="block text-[13px]">Mi Historial Académico</b>
                <span class="block mt-1 text-[#929cad] text-[11px]">Asignaturas cursadas y acreditadas</span>
            </a>
        </div>
    </section>

    {{-- Estados vacíos: solo aparecen cuando realmente no hay datos --}}
    <section class="grid grid-cols-1 lg:grid-cols-2 gap-5 mt-5">
        <article class="overflow-hidden rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
            <div class="flex items-center justify-between px-5 py-5 border-b border-[#f1efed]">
                <h2 class="m-0 text-[#293448] text-base">Mis Calificaciones</h2>
                <a href="{{ route('panel.alumno.calificaciones') }}" class="text-brand-600 text-xs font-extrabold no-underline hover:text-brand-800">Ver todo →</a>
            </div>

            @if ($totalCalificaciones === 0)
                <div class="px-5 py-8 text-center">
                    <div class="grid place-items-center w-[46px] h-[46px] mx-auto mb-3 rounded-xl text-[#98a2b2] bg-cream">
                        <i data-lucide="clipboard-list" class="w-[21px]"></i>
                    </div>
                    <p class="m-0 text-[#748095] text-[13px]">No hay calificaciones disponibles.</p>
                </div>
            @endif
        </article>

        <article class="overflow-hidden rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
            <div class="flex items-center justify-between px-5 py-5 border-b border-[#f1efed]">
                <h2 class="m-0 text-[#293448] text-base">Mi Historial Académico</h2>
                <a href="{{ route('panel.alumno.historial') }}" class="text-brand-600 text-xs font-extrabold no-underline hover:text-brand-800">Ver todo →</a>
            </div>

            @if ($totalHistorial === 0)
                <div class="px-5 py-8 text-center">
                    <div class="grid place-items-center w-[46px] h-[46px] mx-auto mb-3 rounded-xl text-[#98a2b2] bg-cream">
                        <i data-lucide="scroll-text" class="w-[21px]"></i>
                    </div>
                    <p class="m-0 text-[#748095] text-[13px]">No hay historial académico disponible.</p>
                </div>
            @endif
        </article>
    </section>
@endsection
