@extends('panels.jefe-carrera.layout')

@section('miga-de-pan', 'Docentes')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="m-0 text-[#202b3d] text-[26px] md:text-[29px] tracking-tight">Docentes</h1>
            <p class="mt-1.5 mb-6 text-[#748095] text-sm">
                Docentes que <b>pertenecen</b> a tu carrera y docentes que <b>imparten</b> clases en ella.
            </p>
        </div>

        @can('Docentes.crear')
            <button data-toast="Nuevo docente — módulo en desarrollo."
                class="inline-flex items-center gap-2 py-2.5 px-4 rounded-[11px] text-[13px] font-bold text-white bg-gradient-to-br from-brand-500 to-brand-700 shadow-[0_7px_16px_rgba(109,25,56,.18)] transition-all duration-200 hover:-translate-y-0.5">
                <i data-lucide="plus" class="w-4 h-4"></i> Nuevo docente
            </button>
        @endcan
    </div>

    @if ($idCarrera === null)
        <section class="mb-5 p-4 rounded-[17px] border border-[#f0e2c9] bg-[#fdf8ee] text-[#7a5a17] text-[13px] leading-6">
            <b class="block text-[#5f4410]">Sin carrera vinculada</b>
            Tu cuenta no tiene vinculado un registro de jefe de carrera (relación
            <code class="px-1 rounded bg-white/70">usuarios → jefe de carrera → carrera</code>), por lo que no se puede
            determinar qué carrera consultar. Contacta al Administrador para completar tu vinculación.
        </section>
    @endif

    {{-- =========================================================
         A) Docentes que PERTENECEN a mi carrera
         Fuente: tabla intermedia carrera_docente (N:N).
         Aparecen aunque todavía no tengan grupos asignados.
    ========================================================== --}}
    <section class="overflow-hidden rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
        <div class="px-5 py-5 border-b border-[#f1efed]">
            <div class="flex items-center justify-between gap-3">
                <h2 class="m-0 text-[#293448] text-base">Docentes de la carrera</h2>
                <span class="px-2 py-1 rounded-md text-brand-700 bg-brand-100 text-[10px] font-extrabold tracking-[.05em]">
                    {{ $docentesPertenecen->count() }} DOCENTE(S)
                </span>
            </div>
            <p class="mt-1.5 mb-0 text-[#98a2b2] text-[12px]">
                Pertenencia formal registrada en el sistema (<code class="px-1 rounded bg-cream">carrera_docente</code>).
                Se muestran aunque aún no imparten grupos.
            </p>
        </div>

        @if ($docentesPertenecen->isEmpty())
            <div class="px-5 py-10 text-center">
                <div class="grid place-items-center w-[52px] h-[52px] mx-auto mb-4 rounded-2xl text-[#98a2b2] bg-cream">
                    <i data-lucide="user-cog" class="w-[24px]"></i>
                </div>
                <p class="m-0 text-[#748095] text-[14px]">No hay docentes asignados formalmente a esta carrera.</p>

                @if ($idCarrera !== null)
                    <p class="mt-2 mb-0 text-[#98a2b2] text-[12px]">
                        Las asignaciones se registran desde el módulo
                        <b>Docentes</b> o desde <b>Usuarios y Roles</b>; aquí no se deducen de las materias ni de los grupos.
                    </p>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-[#fafafa] text-[#748095] text-[11px] uppercase tracking-[.06em]">
                            <th class="px-5 py-3 font-extrabold">No. empleado</th>
                            <th class="px-5 py-3 font-extrabold">Docente</th>
                            <th class="px-5 py-3 font-extrabold">Correo</th>
                            <th class="px-5 py-3 font-extrabold text-center">Estatus</th>
                            <th class="px-5 py-3 font-extrabold text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="text-[#354156] text-[13px]">
                        @foreach ($docentesPertenecen as $docente)
                            <tr class="border-t border-cream hover:bg-[#fcfafb]">
                                <td class="px-5 py-3.5"><b>{{ $docente->no_empleado }}</b></td>
                                <td class="px-5 py-3.5">
                                    {{ trim($docente->nombre . ' ' . $docente->apellido_paterno . ' ' . ($docente->apellido_materno ?? '')) }}
                                </td>
                                <td class="px-5 py-3.5 text-[#98a2b2]">{{ $docente->email }}</td>
                                <td class="px-5 py-3.5 text-center">
                                    @if ($docente->activo)
                                        <span class="px-2 py-1 rounded-full text-emerald-700 bg-emerald-50 text-[11px] font-extrabold">Activo</span>
                                    @else
                                        <span class="px-2 py-1 rounded-full text-red-700 bg-red-50 text-[11px] font-extrabold">Inactivo</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                    @can('Docentes.editar')
                                        <button data-toast="Editar docente — módulo en desarrollo."
                                            class="px-2.5 py-1.5 rounded-lg text-[11px] font-bold text-brand-700 bg-brand-100 hover:bg-brand-200 transition-all">Editar</button>
                                    @endcan
                                    @can('Docentes.eliminar')
                                        <button data-toast="Eliminar docente — módulo en desarrollo."
                                            class="px-2.5 py-1.5 rounded-lg text-[11px] font-bold text-red-700 bg-red-50 hover:bg-red-100 transition-all">Eliminar</button>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- =========================================================
         B) Docentes que IMPARTEN clases en mi carrera
         Fuente: docentes -> grupos -> materias -> carreras.
         Un docente puede impartir aquí sin pertenecer formalmente.
    ========================================================== --}}
    <section class="mt-5 overflow-hidden rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
        <div class="px-5 py-5 border-b border-[#f1efed]">
            <div class="flex items-center justify-between gap-3">
                <h2 class="m-0 text-[#293448] text-base">Docentes que imparten en la carrera</h2>
                <span class="px-2 py-1 rounded-md text-blue-700 bg-blue-50 text-[10px] font-extrabold tracking-[.05em]">
                    {{ $docentesImparten->count() }} DOCENTE(S)
                </span>
            </div>
            <p class="mt-1.5 mb-0 text-[#98a2b2] text-[12px]">
                Información académica derivada de los grupos y materias asignados
                (<code class="px-1 rounded bg-cream">grupos → materias → carrera</code>). No implica pertenencia formal.
            </p>
        </div>

        @if ($docentesImparten->isEmpty())
            <div class="px-5 py-10 text-center">
                <div class="grid place-items-center w-[52px] h-[52px] mx-auto mb-4 rounded-2xl text-[#98a2b2] bg-cream">
                    <i data-lucide="presentation" class="w-[24px]"></i>
                </div>
                <p class="m-0 text-[#748095] text-[14px]">Ningún docente imparte grupos en esta carrera todavía.</p>

                @if ($idCarrera !== null)
                    <p class="mt-2 mb-0 text-[#98a2b2] text-[12px]">
                        Esta lista se llena cuando se asignan grupos y materias de la carrera a un docente.
                    </p>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-[#fafafa] text-[#748095] text-[11px] uppercase tracking-[.06em]">
                            <th class="px-5 py-3 font-extrabold">No. empleado</th>
                            <th class="px-5 py-3 font-extrabold">Docente</th>
                            <th class="px-5 py-3 font-extrabold">Materias que imparte</th>
                            <th class="px-5 py-3 font-extrabold text-center">Grupos</th>
                            <th class="px-5 py-3 font-extrabold text-center">Estatus</th>
                            <th class="px-5 py-3 font-extrabold text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="text-[#354156] text-[13px]">
                        @foreach ($docentesImparten as $docente)
                            <tr class="border-t border-cream hover:bg-[#fcfafb]">
                                <td class="px-5 py-3.5"><b>{{ $docente->no_empleado }}</b></td>
                                <td class="px-5 py-3.5">
                                    {{ trim($docente->nombre . ' ' . $docente->apellido_paterno . ' ' . ($docente->apellido_materno ?? '')) }}
                                </td>
                                <td class="px-5 py-3.5 text-[#748095]">
                                    <span class="block">{{ $docente->nombres_materias ?? '—' }}</span>
                                    <span class="block mt-0.5 text-[#98a2b2] text-[11px]">
                                        {{ $docente->materias_impartidas }} materia(s)
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <span class="px-2 py-1 rounded-full text-blue-700 bg-blue-50 text-[11px] font-extrabold">
                                        {{ $docente->grupos_impartidos }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    @if ($docente->activo)
                                        <span class="px-2 py-1 rounded-full text-emerald-700 bg-emerald-50 text-[11px] font-extrabold">Activo</span>
                                    @else
                                        <span class="px-2 py-1 rounded-full text-red-700 bg-red-50 text-[11px] font-extrabold">Inactivo</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                    @can('Docentes.editar')
                                        <button data-toast="Editar docente — módulo en desarrollo."
                                            class="px-2.5 py-1.5 rounded-lg text-[11px] font-bold text-brand-700 bg-brand-100 hover:bg-brand-200 transition-all">Editar</button>
                                    @endcan
                                    @can('Docentes.eliminar')
                                        <button data-toast="Eliminar docente — módulo en desarrollo."
                                            class="px-2.5 py-1.5 rounded-lg text-[11px] font-bold text-red-700 bg-red-50 hover:bg-red-100 transition-all">Eliminar</button>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
