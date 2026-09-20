<div class="p-6 bg-white rounded-lg shadow-md">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Matriz de Roles y Permisos</h2>
            <p class="text-sm text-gray-600">Asigna o remueve permisos directamente para cada rol del sistema.</p>
        </div>

        <!-- Botón para procesar los datos al backend -->
        <button 
            wire:click="guardar" 
            class="bg-indigo-600 text-white font-semibold px-5 py-2 rounded-lg hover:bg-indigo-700 transition flex items-center gap-2"
        >
            <span wire:loading.remove wire:target="guardar">Guardar Cambios</span>
            <span wire:loading wire:target="guardar">Guardando...</span>
        </button>
    </div>

    <!-- Alerta de confirmación -->
    @if ($guardado)
        <div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded">
            <p class="font-bold">¡Éxito!</p>
            <p class="text-sm">La matriz de permisos se ha actualizado correctamente en la base de datos.</p>
        </div>
    @endif

    <!-- Tabla con la Matriz -->
    <div class="overflow-x-auto">
        <table class="w-full border-collapse border border-gray-200">
            <thead>
                <tr class="bg-gray-100 text-left">
                    <th class="p-3 border border-gray-200">Módulo / Permiso</th>
                    @foreach ($roles as $role)
                        <th class="p-3 border border-gray-200 text-center text-sm font-bold text-gray-700">
                            {{ $role->nombre_rol ?? $role->nombre }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($modulos as $modulo => $permisos)
                    <!-- Separador de Módulo -->
                    <tr class="bg-gray-50 font-bold text-gray-700">
                        <td colspan="{{ count($roles) + 1 }}" class="p-2 border border-gray-200 uppercase text-xs tracking-wider">
                            Módulo: {{ $modulo ?: 'General' }}
                        </td>
                    </tr>

                    @foreach ($permisos as $permiso)
                        <tr class="hover:bg-gray-50">
                            <td class="p-3 border border-gray-200 text-sm text-gray-800">
                                {{ $permiso->nombre_permiso ?? $permiso->nombre }}
                            </td>

                            <!-- Intersección Rol - Permiso (Checkboxes) -->
                            @foreach ($roles as $role)
                                <td class="p-3 border border-gray-200 text-center">
                                    <input 
                                        type="checkbox" 
                                        wire:model="matriz.{{ $role->id_rol }}.{{ $permiso->id_permiso }}"
                                        class="w-5 h-5 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500 cursor-pointer"
                                    >
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
</div>