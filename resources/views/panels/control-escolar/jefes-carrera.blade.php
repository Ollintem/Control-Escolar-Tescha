@extends('panels.control-escolar.layout')

@section('miga-de-pan', 'Jefes de Carrera')

@section('content')
    {{-- Componente Livewire existente (GestionJefesCarrera), reutilizado sin cambios.
         Control Escolar tiene ver/crear/editar/eliminar sobre "Jefes de Carrera". --}}
    @livewire('gestion-jefes-carrera')
@endsection
