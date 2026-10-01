@extends('panels.control-escolar.layout')

@section('miga-de-pan', 'Docentes')

@section('content')
    {{-- Componente Livewire existente (GestionDocentes), reutilizado sin cambios.
         Control Escolar tiene ver/crear/editar/eliminar sobre "Docentes". --}}
    @livewire('gestion-docentes')
@endsection
