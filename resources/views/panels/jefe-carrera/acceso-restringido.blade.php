@extends('panels.jefe-carrera.layout')

@section('miga-de-pan', 'Acceso no disponible')

@section('content')
    <div class="max-w-2xl mx-auto mt-8 p-6 md:p-8 rounded-2xl border border-amber-200 bg-white shadow-[0_8px_24px_rgba(31,38,50,.06)]">
        <div class="flex items-start gap-4">
            <div class="grid place-items-center w-12 h-12 shrink-0 rounded-xl text-amber-700 bg-amber-50">
                <i data-lucide="shield-alert" class="w-6 h-6"></i>
            </div>
            <div>
                <h1 class="m-0 text-[#202b3d] text-xl font-extrabold">Acceso académico no disponible</h1>
                <p class="mt-2 mb-0 text-[#596579] text-sm leading-6">{{ $mensaje }}</p>
                <p class="mt-3 mb-0 text-[#8791a1] text-xs leading-5">La cuenta y la información académica se conservaron. Contacta a Administración para revisar el perfil y su carrera asignada.</p>
            </div>
        </div>
    </div>
@endsection
