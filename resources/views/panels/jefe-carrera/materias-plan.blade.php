@extends('panels.jefe-carrera.layout')

@section('miga-de-pan', 'Materias y Plan de Estudios')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="m-0 text-[#202b3d] text-[26px] md:text-[29px] tracking-tight">Materias y Plan de Estudios</h1>
            <p class="mt-1.5 mb-6 text-[#748095] text-sm">Retícula de tu carrera. Solo se consulta la información de tu carrera.</p>
        </div>

        {{-- Acción CREAR visible solo si la Matriz lo otorga (Jefe: sí) --}}
        @can('Materias y Plan de Estudios.crear')
            <button data-toast="Nueva materia — módulo en desarrollo."
                class="inline-flex items-center gap-2 py-2.5 px-4 rounded-[11px] text-[13px] font-bold text-white bg-gradient-to-br from-brand-500 to-brand-700 shadow-[0_7px_16px_rgba(109,25,56,.18)] transition-all duration-200 hover:-translate-y-0.5">
                <i data-lucide="plus" class="w-4 h-4"></i> Nueva materia
            </button>
        @endcan
    </div>

    @if ($idCarrera === null)
        <section class="p-4 rounded-[17px] border border-[#f0e2c9] bg-[#fdf8ee] text-[#7a5a17] text-[13px] leading-6">
            Tu cuenta no tiene vinculado un registro de jefe de carrera (relación
            <code class="px-1 rounded bg-white/70">usuarios → jefe de carrera → carrera</code>), por lo que no hay
            materias que consultar.
        </section>
    @endif

    {{-- Planes de estudio de MI carrera --}}
    <section class="overflow-hidden rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)] mt-5">
        <div class="flex items-center justify-between px-5 py-5 border-b border-[#f1efed]">
            <h2 class="m-0 text-[#293448] text-base">Planes de estudio</h2>
            <span class="px-2 py-1 rounded-md text-brand-700 bg-brand-100 text-[10px] font-extrabold tracking-[.05em]">
                {{ $planes->count() }} PLAN(ES)
            </span>
        </div>

        @if ($planes->isEmpty())
            <div class="px-5 py-8 text-center">
                <p class="m-0 text-[#748095] text-[13px]">No hay planes de estudio disponibles.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-[#fafafa] text-[#748095] text-[11px] uppercase tracking-[.06em]">
                            <th class="px-5 py-3 font-extrabold">Clave</th>
                            <th class="px-5 py-3 font-extrabold">Nombre</th>
                            <th class="px-5 py-3 font-extrabold text-center">Año</th>
                            <th class="px-5 py-3 font-extrabold text-center">Estatus</th>
                            <th class="px-5 py-3 font-extrabold text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="text-[#354156] text-[13px]">
                        @foreach ($planes as $plan)
                            <tr class="border-t border-cream hover:bg-[#fcfafb]">
                                <td class="px-5 py-3.5"><b>{{ $plan->clave }}</b></td>
                                <td class="px-5 py-3.5">{{ $plan->nombre }}</td>
                                <td class="px-5 py-3.5 text-center">{{ $plan->anio_publicacion ?? '—' }}</td>
                                <td class="px-5 py-3.5 text-center">
                                    @if ($plan->activo)
                                        <span class="px-2 py-1 rounded-full text-emerald-700 bg-emerald-50 text-[11px] font-extrabold">Activo</span>
                                    @else
                                        <span class="px-2 py-1 rounded-full text-red-700 bg-red-50 text-[11px] font-extrabold">Inactivo</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                    @can('Materias y Plan de Estudios.editar')
                                        <button data-toast="Editar plan — módulo en desarrollo."
                                            class="px-2.5 py-1.5 rounded-lg text-[11px] font-bold text-brand-700 bg-brand-100 hover:bg-brand-200 transition-all">Editar</button>
                                    @endcan
                                    {{-- Eliminar: Jefe de Carrera NO tiene este permiso en la Matriz --}}
                                    @can('Materias y Plan de Estudios.eliminar')
                                        <button data-toast="Eliminar plan — módulo en desarrollo."
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

    {{-- Materias de MI carrera --}}
    <section class="overflow-hidden rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)] mt-5">
        <div class="flex items-center justify-between px-5 py-5 border-b border-[#f1efed]">
            <h2 class="m-0 text-[#293448] text-base">Materias</h2>
            <span class="px-2 py-1 rounded-md text-brand-700 bg-brand-100 text-[10px] font-extrabold tracking-[.05em]">
                {{ $materias->count() }} MATERIA(S)
            </span>
        </div>

        @if ($materias->isEmpty())
            <div class="px-5 py-8 text-center">
                <div class="grid place-items-center w-[46px] h-[46px] mx-auto mb-3 rounded-xl text-[#98a2b2] bg-cream">
                    <i data-lucide="book-open" class="w-[21px]"></i>
                </div>
                <p class="m-0 text-[#748095] text-[13px]">No hay materias disponibles.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-[#fafafa] text-[#748095] text-[11px] uppercase tracking-[.06em]">
                            <th class="px-5 py-3 font-extrabold">Clave</th>
                            <th class="px-5 py-3 font-extrabold">Materia</th>
                            <th class="px-5 py-3 font-extrabold text-center">Créditos</th>
                            <th class="px-5 py-3 font-extrabold text-center">H. teóricas</th>
                            <th class="px-5 py-3 font-extrabold text-center">H. prácticas</th>
                            <th class="px-5 py-3 font-extrabold text-center">Estatus</th>
                            <th class="px-5 py-3 font-extrabold text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="text-[#354156] text-[13px]">
                        @foreach ($materias as $materia)
                            <tr class="border-t border-cream hover:bg-[#fcfafb]">
                                <td class="px-5 py-3.5"><b>{{ $materia->clave }}</b></td>
                                <td class="px-5 py-3.5">{{ $materia->nombre }}</td>
                                <td class="px-5 py-3.5 text-center">{{ $materia->creditos }}</td>
                                <td class="px-5 py-3.5 text-center">{{ $materia->horas_teoricas }}</td>
                                <td class="px-5 py-3.5 text-center">{{ $materia->horas_practicas }}</td>
                                <td class="px-5 py-3.5 text-center">
                                    @if ($materia->activo)
                                        <span class="px-2 py-1 rounded-full text-emerald-700 bg-emerald-50 text-[11px] font-extrabold">Activa</span>
                                    @else
                                        <span class="px-2 py-1 rounded-full text-red-700 bg-red-50 text-[11px] font-extrabold">Inactiva</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                    @can('Materias y Plan de Estudios.editar')
                                        <button data-toast="Editar materia — módulo en desarrollo."
                                            class="px-2.5 py-1.5 rounded-lg text-[11px] font-bold text-brand-700 bg-brand-100 hover:bg-brand-200 transition-all">Editar</button>
                                    @endcan
                                    @can('Materias y Plan de Estudios.eliminar')
                                        <button data-toast="Eliminar materia — módulo en desarrollo."
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
