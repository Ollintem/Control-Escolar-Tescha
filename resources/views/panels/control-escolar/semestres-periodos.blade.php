@extends('panels.control-escolar.layout')

@section('miga-de-pan', 'Semestres / Periodos')

@section('content')
    {{-- Componente Livewire existente (GestionSemestresPeriodos), reutilizado sin cambios.
         Administra semestres ("Carreras y Semestres") y periodos
         ("Periodos Escolares"): Control Escolar tiene ver/crear/editar/eliminar
         en ambos módulos de la Matriz. --}}
    @livewire('gestion-semestres-periodos')
@endsection
