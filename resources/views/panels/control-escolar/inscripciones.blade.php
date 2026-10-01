@extends('panels.control-escolar.layout')

@section('miga-de-pan', 'Inscripciones')

@section('content')
    <h1 class="m-0 text-[#202b3d] text-[26px] md:text-[29px] tracking-tight">Inscripciones</h1>
    <p class="mt-1.5 mb-6 text-[#748095] text-sm">Gestión de inscripciones de alumnos por grupo y periodo.</p>

    {{-- Estado informativo: el módulo todavía no tiene interfaz de gestión.
         No se muestran datos inventados ni se creó lógica académica nueva. --}}
    <section class="p-8 rounded-[17px] border border-[#ece9e6] bg-white text-center shadow-[0_5px_13px_rgba(31,38,50,.04)]">
        <div class="grid place-items-center w-[52px] h-[52px] mx-auto mb-4 rounded-2xl text-[#98a2b2] bg-cream">
            <i data-lucide="file-clock" class="w-[24px]"></i>
        </div>

        <p class="m-0 text-[#748095] text-[14px]">No hay información disponible.</p>

        <p class="mt-2 mb-0 text-[#98a2b2] text-[12px] leading-6">
            Este módulo aún no cuenta con una interfaz de gestión. Se implementará
            en una etapa independiente.
        </p>
    </section>
@endsection
