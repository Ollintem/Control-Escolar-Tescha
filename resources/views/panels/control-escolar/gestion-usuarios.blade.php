@extends('panels.control-escolar.layout')

@section('miga-de-pan', 'Gestión de Usuarios')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="m-0 text-[#202b3d] text-[26px] md:text-[29px] tracking-tight">Gestión de Usuarios</h1>
            <p class="mt-1.5 mb-6 text-[#748095] text-sm">Consulta de cuentas de acceso al sistema.</p>
        </div>

        {{-- Acción CREAR visible solo si la Matriz lo otorga (Control Escolar: sí) --}}
        @can('Usuarios y Roles.crear')
            <button data-toast="Alta de usuarios — módulo en desarrollo."
                class="inline-flex items-center gap-2 py-2.5 px-4 rounded-[11px] text-[13px] font-bold text-white bg-gradient-to-br from-brand-500 to-brand-700 shadow-[0_7px_16px_rgba(109,25,56,.18)] transition-all duration-200 hover:-translate-y-0.5">
                <i data-lucide="plus" class="w-4 h-4"></i> Nuevo usuario
            </button>
        @endcan
    </div>

    <section class="overflow-hidden rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
        <div class="flex items-center justify-between px-5 py-5 border-b border-[#f1efed]">
            <h2 class="m-0 text-[#293448] text-base">Cuentas registradas</h2>
            <span class="px-2 py-1 rounded-md text-brand-700 bg-brand-100 text-[10px] font-extrabold tracking-[.05em]">
                {{ $usuarios->count() }} CUENTA(S)
            </span>
        </div>

        @if ($usuarios->isEmpty())
            <div class="px-5 py-10 text-center">
                <div class="grid place-items-center w-[52px] h-[52px] mx-auto mb-4 rounded-2xl text-[#98a2b2] bg-cream">
                    <i data-lucide="user-round-cog" class="w-[24px]"></i>
                </div>
                <p class="m-0 text-[#748095] text-[14px]">No hay usuarios disponibles.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-[#fafafa] text-[#748095] text-[11px] uppercase tracking-[.06em]">
                            <th class="px-5 py-3 font-extrabold">Nombre</th>
                            <th class="px-5 py-3 font-extrabold">Correo</th>
                            <th class="px-5 py-3 font-extrabold">Rol</th>
                            <th class="px-5 py-3 font-extrabold text-center">Estatus</th>
                            <th class="px-5 py-3 font-extrabold text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="text-[#354156] text-[13px]">
                        @foreach ($usuarios as $usuario)
                            <tr class="border-t border-cream hover:bg-[#fcfafb]">
                                <td class="px-5 py-3.5"><b>{{ $usuario->name }}</b></td>
                                <td class="px-5 py-3.5 text-[#98a2b2]">{{ $usuario->email }}</td>
                                <td class="px-5 py-3.5">
                                    <span class="px-2 py-1 rounded-full text-brand-700 bg-brand-100 text-[11px] font-extrabold">
                                        {{ $usuario->rol ?? 'Sin rol' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    @if ($usuario->activo)
                                        <span class="px-2 py-1 rounded-full text-emerald-700 bg-emerald-50 text-[11px] font-extrabold">Activo</span>
                                    @else
                                        <span class="px-2 py-1 rounded-full text-red-700 bg-red-50 text-[11px] font-extrabold">Inactivo</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                    @can('Usuarios y Roles.editar')
                                        <button data-toast="Editar usuario — módulo en desarrollo."
                                            class="px-2.5 py-1.5 rounded-lg text-[11px] font-bold text-brand-700 bg-brand-100 hover:bg-brand-200 transition-all">Editar</button>
                                    @endcan
                                    {{-- Sin botón "Eliminar" ni "Desactivar":
                                         Control Escolar NO tiene 'Usuarios y Roles.eliminar'
                                         en la Matriz de Permisos. --}}
                                    @can('Usuarios y Roles.eliminar')
                                        <button data-toast="Eliminar usuario — módulo en desarrollo."
                                            class="px-2.5 py-1.5 rounded-lg text-[11px] font-bold text-red-700 bg-red-50 hover:bg-red-100 transition-all">Eliminar</button>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- Explicación de los límites de este rol, derivada de los permisos actuales --}}
    <section class="mt-5 p-4 rounded-[17px] border border-[#f0e2c9] bg-[#fdf8ee] text-[#7a5a17] text-[13px] leading-6">
        <b class="block text-[#5f4410]">Permisos de tu rol en este módulo</b>
        Control Escolar puede <b>consultar, crear y editar</b> cuentas.
        Las acciones de <b>desactivación y eliminación no están habilitadas</b> para tu rol y no se
        muestran aquí.
    </section>
@endsection
