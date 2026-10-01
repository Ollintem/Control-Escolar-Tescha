@extends('panels.control-escolar.layout')

@section('miga-de-pan', 'Alumnos')

@section('content')
    {{-- Componente Livewire existente (GestionAlumnos), reutilizado sin cambios.
         Control Escolar tiene ver/crear/editar/eliminar sobre "Alumnos" en la Matriz. --}}
    @livewire('gestion-alumnos')
@endsection
