@extends('panels.control-escolar.layout')

@section('miga-de-pan', 'Grupos y Asignaciones')

@section('content')
    {{-- Componente Livewire existente (GestionGrupos), reutilizado sin cambios.
         Control Escolar tiene ver/crear/editar/eliminar sobre "Grupos y Asignaciones". --}}
    @livewire('gestion-grupos')
@endsection
