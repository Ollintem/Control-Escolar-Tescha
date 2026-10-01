@extends('panels.alumno.layout')

@section('miga-de-pan', 'Mi Historial Académico')

@section('content')
    <h1 class="m-0 text-[#202b3d] text-[26px] md:text-[29px] tracking-tight">Mi Historial Académico</h1>
    <p class="mt-1.5 mb-6 text-[#748095] text-sm">
        Asignaturas cursadas y su acreditación. Aquí solo se consulta la información de tu propia cuenta.
    </p>

    <section class="overflow-hidden rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
        <div class="flex items-center justify-between px-5 py-5 border-b border-[#f1efed]">
            <h2 class="m-0 text-[#293448] text-base">Asignaturas cursadas</h2>
            <span class="px-2 py-1 rounded-md text-brand-700 bg-brand-100 text-[10px] font-extrabold tracking-[.05em]">
                {{ $historial->count() }} REGISTRO(S)
            </span>
        </div>

        @if ($historial->isEmpty())
            <div class="px-5 py-12 text-center">
                <div class="grid place-items-center w-[52px] h-[52px] mx-auto mb-4 rounded-2xl text-[#98a2b2] bg-cream">
                    <i data-lucide="scroll-text" class="w-[24px]"></i>
                </div>
                <p class="m-0 text-[#748095] text-[14px]">No hay historial académico disponible.</p>

                @if ($idAlumno === null)
                    <p class="mt-2 mb-0 text-[#98a2b2] text-[12px]">
                        Tu cuenta todavía no tiene vinculado un registro académico (relación
                        <code class="px-1 rounded bg-cream">usuarios → alumno</code>), por lo que no hay asignaturas que
                        consultar. Contacta a Control Escolar.
                    </p>
                @else
                    <p class="mt-2 mb-0 text-[#98a2b2] text-[12px]">
                        El historial se llena conforme se acrediten asignaturas.
                    </p>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-[#fafafa] text-[#748095] text-[11px] uppercase tracking-[.06em]">
                            <th class="px-5 py-3 font-extrabold">Materia</th>
                            <th class="px-5 py-3 font-extrabold">Periodo</th>
                            <th class="px-5 py-3 font-extrabold text-center">Calificación</th>
                            <th class="px-5 py-3 font-extrabold">Acreditación</th>
                            <th class="px-5 py-3 font-extrabold text-center">Estatus</th>
                        </tr>
                    </thead>
                    <tbody class="text-[#354156] text-[13px]">
                        @foreach ($historial as $registro)
                            <tr class="border-t border-cream hover:bg-[#fcfafb]">
                                <td class="px-5 py-3.5">
                                    <b class="block">{{ $registro->materia ?? 'Materia no disponible' }}</b>
                                    <span class="block mt-0.5 text-[#98a2b2] text-[11px]">{{ $registro->clave_materia }}</span>
                                </td>
                                <td class="px-5 py-3.5">{{ $registro->periodo ?? '—' }}</td>
                                <td class="px-5 py-3.5 text-center">
                                    <b class="text-brand-700">{{ $registro->calificacion ?? '—' }}</b>
                                </td>
                                <td class="px-5 py-3.5">{{ $registro->tipo_acreditacion ?? 'ordinario' }}</td>
                                <td class="px-5 py-3.5 text-center">
                                    @if ($registro->aprobada)
                                        <span class="px-2 py-1 rounded-full text-emerald-700 bg-emerald-50 text-[11px] font-extrabold">Aprobada</span>
                                    @else
                                        <span class="px-2 py-1 rounded-full text-red-700 bg-red-50 text-[11px] font-extrabold">No aprobada</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
