@extends('panels.jefe-carrera.layout')

@section('miga-de-pan', 'Grupos')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="m-0 text-[#202b3d] text-[26px] md:text-[29px] tracking-tight">Grupos</h1>
            <p class="mt-1.5 mb-6 text-[#748095] text-sm">Grupos de las materias de tu carrera.</p>
        </div>

        @can('Grupos y Asignaciones.crear')
            <button data-toast="Nuevo grupo — módulo en desarrollo."
                class="inline-flex items-center gap-2 py-2.5 px-4 rounded-[11px] text-[13px] font-bold text-white bg-gradient-to-br from-brand-500 to-brand-700 shadow-[0_7px_16px_rgba(109,25,56,.18)] transition-all duration-200 hover:-translate-y-0.5">
                <i data-lucide="plus" class="w-4 h-4"></i> Nuevo grupo
            </button>
        @endcan
    </div>

    <section class="overflow-hidden rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
        <div class="flex items-center justify-between px-5 py-5 border-b border-[#f1efed]">
            <h2 class="m-0 text-[#293448] text-base">Grupos de la carrera</h2>
            <span class="px-2 py-1 rounded-md text-brand-700 bg-brand-100 text-[10px] font-extrabold tracking-[.05em]">
                {{ $grupos->count() }} GRUPO(S)
            </span>
        </div>

        @if ($grupos->isEmpty())
            <div class="px-5 py-10 text-center">
                <div class="grid place-items-center w-[52px] h-[52px] mx-auto mb-4 rounded-2xl text-[#98a2b2] bg-cream">
                    <i data-lucide="grid-2x2" class="w-[24px]"></i>
                </div>
                <p class="m-0 text-[#748095] text-[14px]">No hay grupos disponibles.</p>

                @if ($idCarrera === null)
                    <p class="mt-2 mb-0 text-[#98a2b2] text-[12px]">
                        Tu cuenta no tiene vinculado un registro de jefe de carrera (relación
                        <code class="px-1 rounded bg-cream">usuarios → jefe de carrera → carrera</code>).
                    </p>
                @else
                    <p class="mt-2 mb-0 text-[#98a2b2] text-[12px]">
                        Aún no hay grupos creados para las materias de tu carrera.
                    </p>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-[#fafafa] text-[#748095] text-[11px] uppercase tracking-[.06em]">
                            <th class="px-5 py-3 font-extrabold">Grupo</th>
                            <th class="px-5 py-3 font-extrabold">Materia</th>
                            <th class="px-5 py-3 font-extrabold">Periodo</th>
                            <th class="px-5 py-3 font-extrabold text-center">Cupo</th>
                            <th class="px-5 py-3 font-extrabold text-center">Estatus</th>
                            <th class="px-5 py-3 font-extrabold text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="text-[#354156] text-[13px]">
                        @foreach ($grupos as $grupo)
                            <tr class="border-t border-cream hover:bg-[#fcfafb]">
                                <td class="px-5 py-3.5"><b>{{ $grupo->grupo }}</b></td>
                                <td class="px-5 py-3.5">{{ $grupo->materia ?? '—' }}</td>
                                <td class="px-5 py-3.5">{{ $grupo->periodo ?? '—' }}</td>
                                <td class="px-5 py-3.5 text-center">{{ $grupo->cupo_maximo }}</td>
                                <td class="px-5 py-3.5 text-center">
                                    @if ($grupo->activo)
                                        <span class="px-2 py-1 rounded-full text-emerald-700 bg-emerald-50 text-[11px] font-extrabold">Activo</span>
                                    @else
                                        <span class="px-2 py-1 rounded-full text-red-700 bg-red-50 text-[11px] font-extrabold">Inactivo</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                    @can('Grupos y Asignaciones.editar')
                                        <button data-toast="Editar grupo — módulo en desarrollo."
                                            class="px-2.5 py-1.5 rounded-lg text-[11px] font-bold text-brand-700 bg-brand-100 hover:bg-brand-200 transition-all">Editar</button>
                                    @endcan
                                    @can('Grupos y Asignaciones.eliminar')
                                        <button data-toast="Eliminar grupo — módulo en desarrollo."
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
