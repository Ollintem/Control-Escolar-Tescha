<div class="p-6 bg-white rounded-lg shadow max-w-md mx-auto mt-6">
    <h2 class="text-xl font-bold mb-4">Agregar Nuevo Rol</h2>

    @if (session()->has('message'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
            {{ session('message') }}
        </div>
    @endif

    <form wire:submit.prevent="guardar">
        <div class="mb-4">
            <label class="block text-gray-700 font-semibold mb-1">Nombre del Rol</label>
            <input 
                type="text" 
                wire:model="nombre_rol" 
                class="w-full border border-gray-300 p-2 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500"
                placeholder="Ej. Administrador"
            >
            @error('nombre_rol') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 font-semibold mb-1">Descripción</label>
            <textarea 
                wire:model="descripcion" 
                class="w-full border border-gray-300 p-2 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500"
                placeholder="Descripción del rol"
            ></textarea>
            @error('descripcion') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <button 
            type="submit" 
            class="w-full bg-indigo-600 text-white font-bold py-2 rounded hover:bg-indigo-700 transition"
        >
            Guardar Rol
        </button>
    </form>
</div>