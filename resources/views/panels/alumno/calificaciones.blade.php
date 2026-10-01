@extends('panels.alumno.layout')

@section('miga-de-pan', 'Mis Calificaciones')

@section('content')
    <h1 class="m-0 text-[#202b3d] text-[26px] md:text-[29px] tracking-tight">Mis Calificaciones</h1>
    <p class="mt-1.5 mb-6 text-[#748095] text-sm">
        Calificaciones capturadas en tus inscripciones. Aquí solo se consulta la información de tu propia cuenta.
    </p>

    <section class="overflow-hidden rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
        <div class="flex items-center justify-between px-5 py-5 border-b border-[#f1efed]">
            <h2 class="m-0 text-[#293448] text-base">Parciales registrados</h2>
            <span class="px-2 py-1 rounded-md text-brand-700 bg-brand-100 text-[10px] font-extrabold tracking-[.05em]">
                {{ $calificaciones->count() }} REGISTRO(S)
            </span>
        </div>

        @if ($calificaciones->isEmpty())
            <div class="px-5 py-12 text-center">
                <div class="grid place-items-center w-[52px] h-[52px] mx-auto mb-4 rounded-2xl text-[#98a2b2] bg-cream">
                    <i data-lucide="clipboard-list" class="w-[24px]"></i>
                </div>
                <p class="m-0 text-[#748095] text-[14px]">No hay calificaciones disponibles.</p>

                @if ($idAlumno === null)
                    <p class="mt-2 mb-0 text-[#98a2b2] text-[12px]">
                        Tu cuenta todavía no tiene vinculado un registro académico (relación
                        <code class="px-1 rounded bg-cream">usuarios → alumno</code>), por lo que no hay materias que
                        consultar. Contacta a Control Escolar.
                    </p>
                @else
                    <p class="mt-2 mb-0 text-[#98a2b2] text-[12px]">
                        Cuando tu docente capture un parcial, aparecerá aquí.
                    </p>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-[#fafafa] text-[#748095] text-[11px] uppercase tracking-[.06em]">
                            <th class="px-5 py-3 font-extrabold">Materia</th>
                            <th class="px-5 py-3 font-extrabold">Grupo</th>
                            <th class="px-5 py-3 font-extrabold">Periodo</th>
                            <th class="px-5 py-3 font-extrabold text-center">Parcial</th>
                            <th class="px-5 py-3 font-extrabold text-center">Calificación</th>
                            <th class="px-5 py-3 font-extrabold">Observaciones</th>
                        </tr>
                    </thead>
                    <tbody class="text-[#354156] text-[13px]">
                        @foreach ($calificaciones as $registro)
                            <tr class="border-t border-cream hover:bg-[#fcfafb]">
                                <td class="px-5 py-3.5">
                                    <b class="block">{{ $registro->materia ?? 'Materia no disponible' }}</b>
                                    <span class="block mt-0.5 text-[#98a2b2] text-[11px]">{{ $registro->clave_materia }}</span>
                                </td>
                                <td class="px-5 py-3.5">{{ $registro->grupo ?? '—' }}</td>
                                <td class="px-5 py-3.5">{{ $registro->periodo ?? '—' }}</td>
                                <td class="px-5 py-3.5 text-center">{{ $registro->parcial ?? '—' }}</td>
                                <td class="px-5 py-3.5 text-center">
                                    <b class="text-brand-700">{{ $registro->calificacion ?? '—' }}</b>
                                </td>
                                <td class="px-5 py-3.5 text-[#98a2b2]">{{ $registro->observaciones ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
