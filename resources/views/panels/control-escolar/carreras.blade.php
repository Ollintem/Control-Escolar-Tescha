@extends('panels.control-escolar.layout')

@section('miga-de-pan', 'Carreras')

@section('content')
    {{-- Componente Livewire existente (GestionCarreras), reutilizado sin cambios.
         Control Escolar tiene ver/crear/editar/eliminar sobre "Carreras y Semestres". --}}
    @livewire('gestion-carreras')
@endsection
