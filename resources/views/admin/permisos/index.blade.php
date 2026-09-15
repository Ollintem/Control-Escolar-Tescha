@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Gestión de Permisos por Rol</h2>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <!-- Selector de Rol -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" action="{{ route('permisos.index') }}" class="row align-items-center">
                <label for="role_id" class="col-auto col-form-label fw-bold">Seleccionar Rol:</label>
                <div class="col-auto">
                    <select name="role_id" id="role_id" class="form-select" onchange="this.form.submit()">
                        @foreach($roles as $role)
                            <option value="{{ $role->id_rol }}" {{ $roleSeleccionado && $roleSeleccionado->id_rol == $role->id_rol ? 'selected' : '' }}>
                                {{ $role->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Matriz de Permisos -->
    @if($roleSeleccionado)
    <form method="POST" action="{{ route('permisos.update') }}">
        @csrf
        <input type="hidden" name="role_id" value="{{ $roleSeleccionado->id_rol }}">

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Módulo</th>
                            <th class="text-center">Ver</th>
                            <th class="text-center">Crear</th>
                            <th class="text-center">Editar</th>
                            <th class="text-center">Eliminar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($permisos as $modulo => $acciones)
                            <tr>
                                <td class="fw-bold">{{ $modulo }}</td>
                                @foreach(['ver', 'crear', 'editar', 'eliminar'] as $accion)
                                    @php
                                        $permiso = $acciones->firstWhere('accion', $accion);
                                    @endphp
                                    <td class="text-center">
                                        @if($permiso)
                                            <div class="form-check form-switch d-inline-block">
                                                <input class="form-check-input" type="checkbox" name="permisos[]" 
                                                    value="{{ $permiso->id_permiso }}" 
                                                    {{ in_array($permiso->id_permiso, $permisosExistentes) ? 'checked' : '' }}>
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-end bg-white py-3">
                <button type="submit" class="btn btn-primary px-4">Guardar Cambios</button>
            </div>
        </div>
    </form>
    @endif
</div>
@endsection
