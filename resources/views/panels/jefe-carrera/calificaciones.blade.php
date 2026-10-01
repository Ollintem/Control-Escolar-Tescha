@extends('panels.jefe-carrera.layout')

@section('miga-de-pan', 'Calificaciones')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="m-0 text-[#202b3d] text-[26px] md:text-[29px] tracking-tight">Calificaciones</h1>
            <p class="mt-1.5 mb-6 text-[#748095] text-sm">Calificaciones capturadas en grupos de las materias de tu carrera.</p>
        </div>

        @can('Calificaciones y Actas.crear')
            <button data-toast="Capturar calificación — módulo en desarrollo."
                class="inline-flex items-center gap-2 py-2.5 px-4 rounded-[11px] text-[13px] font-bold text-white bg-gradient-to-br from-brand-500 to-brand-700 shadow-[0_7px_16px_rgba(109,25,56,.18)] transition-all duration-200 hover:-translate-y-0.5">
                <i data-lucide="plus" class="w-4 h-4"></i> Capturar calificación
            </button>
        @endcan
    </div>

    <section class="overflow-hidden rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
        <div class="flex items-center justify-between px-5 py-5 border-b border-[#f1efed]">
            <h2 class="m-0 text-[#293448] text-base">Calificaciones de la carrera</h2>
            <span class="px-2 py-1 rounded-md text-brand-700 bg-brand-100 text-[10px] font-extrabold tracking-[.05em]">
                {{ $calificaciones->count() }} REGISTRO(S)
            </span>
        </div>

        @if ($calificaciones->isEmpty())
            <div class="px-5 py-10 text-center">
                <div class="grid place-items-center w-[52px] h-[52px] mx-auto mb-4 rounded-2xl text-[#98a2b2] bg-cream">
                    <i data-lucide="clipboard-list" class="w-[24px]"></i>
                </div>
                <p class="m-0 text-[#748095] text-[14px]">No hay calificaciones disponibles.</p>

                @if ($idCarrera === null)
                    <p class="mt-2 mb-0 text-[#98a2b2] text-[12px]">
                        Tu cuenta no tiene vinculado un registro de jefe de carrera (relación
                        <code class="px-1 rounded bg-cream">usuarios → jefe de carrera → carrera</code>).
                    </p>
                @else
                    <p class="mt-2 mb-0 text-[#98a2b2] text-[12px]">
                        Aún no hay calificaciones capturadas en los grupos de tu carrera.
                    </p>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-[#fafafa] text-[#748095] text-[11px] uppercase tracking-[.06em]">
                            <th class="px-5 py-3 font-extrabold">No. control</th>
                            <th class="px-5 py-3 font-extrabold">Alumno</th>
                            <th class="px-5 py-3 font-extrabold">Materia</th>
                            <th class="px-5 py-3 font-extrabold">Grupo</th>
                            <th class="px-5 py-3 font-extrabold text-center">Parcial</th>
                            <th class="px-5 py-3 font-extrabold text-center">Calificación</th>
                            <th class="px-5 py-3 font-extrabold text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="text-[#354156] text-[13px]">
                        @foreach ($calificaciones as $registro)
                            <tr class="border-t border-cream hover:bg-[#fcfafb]">
                                <td class="px-5 py-3.5"><b>{{ $registro->no_control ?? '—' }}</b></td>
                                <td class="px-5 py-3.5">
                                    {{ trim(($registro->nombre_alumno ?? '') . ' ' . ($registro->apellido_paterno ?? '') . ' ' . ($registro->apellido_materno ?? '')) ?: '—' }}
                                </td>
                                <td class="px-5 py-3.5">{{ $registro->materia ?? '—' }}</td>
                                <td class="px-5 py-3.5">{{ $registro->grupo ?? '—' }}</td>
                                <td class="px-5 py-3.5 text-center">{{ $registro->parcial ?? '—' }}</td>
                                <td class="px-5 py-3.5 text-center">
                                    <b class="text-brand-700">{{ $registro->calificacion ?? '—' }}</b>
                                </td>
                                <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                    @can('Calificaciones y Actas.editar')
                                        <button data-toast="Editar calificación — módulo en desarrollo."
                                            class="px-2.5 py-1.5 rounded-lg text-[11px] font-bold text-brand-700 bg-brand-100 hover:bg-brand-200 transition-all">Editar</button>
                                    @endcan
                                    @can('Calificaciones y Actas.eliminar')
                                        <button data-toast="Eliminar calificación — módulo en desarrollo."
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
