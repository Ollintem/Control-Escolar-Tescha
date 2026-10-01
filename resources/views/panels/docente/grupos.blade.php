@extends('panels.docente.layout')

@section('miga-de-pan', 'Mis Grupos')

@section('content')
    <h1 class="m-0 text-[#202b3d] text-[26px] md:text-[29px] tracking-tight">Mis Grupos</h1>
    <p class="mt-1.5 mb-6 text-[#748095] text-sm">
        Grupos donde apareces como docente titular. Aquí solo se consulta la información de tu propia cuenta.
    </p>

    <section class="overflow-hidden rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
        <div class="flex items-center justify-between px-5 py-5 border-b border-[#f1efed]">
            <h2 class="m-0 text-[#293448] text-base">Grupos asignados</h2>
            <span class="px-2 py-1 rounded-md text-brand-700 bg-brand-100 text-[10px] font-extrabold tracking-[.05em]">
                {{ $grupos->count() }} GRUPO(S)
            </span>
        </div>

        @if ($grupos->isEmpty())
            <div class="px-5 py-12 text-center">
                <div class="grid place-items-center w-[52px] h-[52px] mx-auto mb-4 rounded-2xl text-[#98a2b2] bg-cream">
                    <i data-lucide="grid-2x2" class="w-[24px]"></i>
                </div>
                <p class="m-0 text-[#748095] text-[14px]">No hay grupos disponibles.</p>

                @if ($idDocente === null)
                    <p class="mt-2 mb-0 text-[#98a2b2] text-[12px]">
                        Tu cuenta todavía no tiene vinculado un registro docente (relación
                        <code class="px-1 rounded bg-cream">usuarios → docente</code>). Contacta a Control Escolar.
                    </p>
                @else
                    <p class="mt-2 mb-0 text-[#98a2b2] text-[12px]">
                        Cuando Control Escolar asigne grupos a tu registro docente, aparecerán aquí.
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
                            <th class="px-5 py-3 font-extrabold text-center">Créditos</th>
                            <th class="px-5 py-3 font-extrabold text-center">Cupo</th>
                            <th class="px-5 py-3 font-extrabold text-center">Estatus</th>
                        </tr>
                    </thead>
                    <tbody class="text-[#354156] text-[13px]">
                        @foreach ($grupos as $grupo)
                            <tr class="border-t border-cream hover:bg-[#fcfafb]">
                                <td class="px-5 py-3.5">
                                    <b class="block">{{ $grupo->grupo }}</b>
                                    <span class="block mt-0.5 text-[#98a2b2] text-[11px]">{{ $grupo->clave_materia }}</span>
                                </td>
                                <td class="px-5 py-3.5">{{ $grupo->materia ?? '—' }}</td>
                                <td class="px-5 py-3.5">{{ $grupo->periodo ?? '—' }}</td>
                                <td class="px-5 py-3.5 text-center">{{ $grupo->creditos ?? '—' }}</td>
                                <td class="px-5 py-3.5 text-center">{{ $grupo->cupo_maximo }}</td>
                                <td class="px-5 py-3.5 text-center">
                                    @if ($grupo->activo)
                                        <span class="px-2 py-1 rounded-full text-emerald-700 bg-emerald-50 text-[11px] font-extrabold">Activo</span>
                                    @else
                                        <span class="px-2 py-1 rounded-full text-red-700 bg-red-50 text-[11px] font-extrabold">Inactivo</span>
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
