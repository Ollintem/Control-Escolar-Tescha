<div>
  <!-- Header -->
  <h1 class="m-0 text-[#202b3d] text-[26px] md:text-[29px] tracking-tight">Gestión de usuarios y roles</h1>
  <p class="mt-1.5 mb-6 text-[#748095] text-sm">Administra los perfiles del sistema y configura los permisos de acceso para cada rol.</p>

  <!-- Barra de búsqueda y botón -->
  <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 p-4 rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_8px_22px_rgba(31,38,50,.045)]">
    <div class="flex items-center gap-2.5 flex-1 sm:max-w-[430px] px-3.5 py-2.5 rounded-[11px] border border-[#e8e4e1] bg-[#faf9f8] text-[#8b95a5]">
      <i data-lucide="search" class="w-[17px]"></i>
      <input type="search" wire:model.live="search" placeholder="Buscar rol por nombre o descripción..." class="w-full border-0 outline-none bg-transparent text-[#253146] text-[13px] font-medium">
    </div>
    <button type="button" wire:click="abrirModalCrear"
      class="inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-[11px] text-white bg-gradient-to-br from-brand-500 to-brand-700 shadow-[0_7px_16px_rgba(109,25,56,.18)] text-[13px] font-bold transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_10px_20px_rgba(109,25,56,.23)]">
      <i data-lucide="plus" class="w-4 h-4"></i>
      Nuevo rol
    </button>
  </div>

  <!-- Mensajes flash (se ocultan automáticamente en 3s) -->
  @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
      x-transition:leave="transition ease-in duration-300"
      x-transition:leave-start="opacity-100 translate-y-0"
      x-transition:leave-end="opacity-0 -translate-y-2"
      class="mt-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-[11px] text-sm font-semibold shadow-sm flex items-center gap-2">
      <i data-lucide="check-circle" class="w-4 h-4 text-emerald-500"></i>
      {{ session('success') }}
    </div>
  @endif

  @if(session('error'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
      x-transition:leave="transition ease-in duration-300"
      x-transition:leave-start="opacity-100 translate-y-0"
      x-transition:leave-end="opacity-0 -translate-y-2"
      class="mt-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-[11px] text-sm font-semibold shadow-sm flex items-center gap-2">
      <i data-lucide="alert-circle" class="w-4 h-4 text-red-500"></i>
      {{ session('error') }}
    </div>
  @endif

  <!-- Tabla de roles -->
  <section class="mt-4 overflow-hidden rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_8px_22px_rgba(31,38,50,.045)]">
    <div class="flex items-center justify-between px-5 py-5 border-b border-[#efedeb]">
      <div>
        <h2 class="m-0 text-[#293448] text-base">Catálogo de roles</h2>
        <span class="text-[#919aaa] text-[11px]">Perfiles disponibles dentro del sistema TESCHA</span>
      </div>
      <span class="text-[#919aaa] text-[11px]">{{ $roleCount }} roles registrados</span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full border-collapse min-w-[760px]">
        <thead>
          <tr>
            <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Rol / Perfil</th>
            <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Permisos</th>
            <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Estatus</th>
            <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Acciones</th>
          </tr>
        </thead>
        <tbody>
          @forelse($roles as $role)
            @php $isAdmin = $role['id_rol'] == 1; @endphp
            <tr class="transition-colors duration-150 hover:bg-[#fcfafb]">
              <td class="px-5 py-4 border-b border-[#f2efed]">
                <div class="flex items-center gap-3 min-w-[220px]">
                  <div class="grid place-items-center w-10 h-10 shrink-0 rounded-xl {{ $isAdmin ? 'text-gold-500 bg-amber-50' : 'text-brand-500 bg-brand-100' }}">
                    <i data-lucide="{{ $isAdmin ? 'shield-check' : 'shield' }}" class="w-[19px] h-[19px]"></i>
                  </div>
                  <div>
                    <span class="block text-[#263247] font-extrabold text-[13px]">
                      {{ $role['nombre'] }}
                      @if($isAdmin)
                        <span class="ml-1 align-middle text-[9px] font-extrabold text-gold-500 bg-amber-50 px-1.5 py-0.5 rounded">PROTEGIDO</span>
                      @endif
                    </span>
                    <span class="block mt-0.5 text-[#99a2b0] text-[11px]">{{ $role['descripcion'] ?: 'Perfil de acceso del sistema' }}</span>
                  </div>
                </div>
              </td>
              <td class="px-5 py-4 border-b border-[#f2efed] text-[#39465a] text-[13px]">
                <strong>{{ $role['permisos_count'] ?? 0 }}</strong>
              </td>
              <td class="px-5 py-4 border-b border-[#f2efed]">
                <span class="inline-flex items-center gap-1.5 text-emerald-700 text-[11px] font-bold">
                  <span class="w-[7px] h-[7px] rounded-full bg-emerald-500 inline-block"></span>
                  Activo
                </span>
              </td>
              <td class="px-5 py-4 border-b border-[#f2efed]">
                <div class="flex gap-1.5">
                  <button wire:click="abrirModalEditar({{ $role['id_rol'] }})" title="Editar rol"
                    class="grid place-items-center w-[34px] h-[34px] rounded-[9px] border border-[#e9e5e2] text-[#687488] bg-white transition-all duration-200 hover:text-brand-600 hover:border-[#d9bcc7] hover:bg-[#fff9fb] hover:scale-110">
                    <i data-lucide="pencil" class="w-4 h-4"></i>
                  </button>
                  @if(!$isAdmin)
                    <button wire:click="eliminar({{ $role['id_rol'] }})" wire:confirm="¿Eliminar este rol?"
                      title="Eliminar rol"
                      class="grid place-items-center w-[34px] h-[34px] rounded-[9px] border border-[#e9e5e2] text-[#687488] bg-white transition-all duration-200 hover:text-red-600 hover:border-red-200 hover:bg-red-50 hover:scale-110">
                      <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                  @else
                    <button disabled title="El rol Administrador no se puede eliminar"
                      class="grid place-items-center w-[34px] h-[34px] rounded-[9px] border border-[#e9e5e2] text-[#c7cbd2] bg-[#fafafa] cursor-not-allowed">
                      <i data-lucide="lock" class="w-4 h-4"></i>
                    </button>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="p-9 text-center text-[#8993a4] text-[13px]">No se encontraron roles con esa búsqueda.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>

  <!-- Modal Crear / Editar Rol -->
  @if($showModal)
    <div class="fixed inset-0 z-[1000] grid place-items-center p-5 bg-black/55 backdrop-blur-sm" wire:click.self="$set('showModal', false)">
      <div class="w-full max-w-[520px] max-h-[calc(100vh-40px)] overflow-auto rounded-[20px] bg-white shadow-2xl" @click.outside="$set('showModal', false)">
        <div class="flex items-start justify-between gap-4 px-6 pt-6 pb-4 border-b border-[#eeeae8]">
          <div>
            <h2 class="m-0 text-[#283348] text-lg">{{ $editingRoleId ? 'Editar rol' : 'Nuevo rol' }}</h2>
            <p class="mt-1 mb-0 text-[#919aaa] text-[11px]">{{ $editingRoleId ? 'Actualiza la información del perfil seleccionado.' : 'Registra un nuevo perfil de acceso para TESCHA.' }}</p>
          </div>
          <button type="button" wire:click="$set('showModal', false)"
            class="grid place-items-center w-[34px] h-[34px] rounded-[9px] text-[#788395] bg-[#f7f5f3] transition-colors hover:text-brand-700 hover:bg-brand-100">
            <i data-lucide="x" class="w-4 h-4"></i>
          </button>
        </div>
        <div class="p-6">
          <div class="mb-4">
            <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Nombre del rol</label>
            <input type="text" wire:model="nombre" placeholder="Ej. Coordinador Académico"
              {{ $editingRoleId == 1 ? 'readonly' : '' }}
              class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
            @error('nombre') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
          </div>
          <div class="mb-4">
            <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Descripción</label>
            <textarea wire:model="descripcion" placeholder="Describe brevemente las funciones de este perfil..."
              class="w-full min-h-[86px] py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] resize-y focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10"></textarea>
            @error('descripcion') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
          </div>
        </div>
        <div class="flex justify-end gap-2 px-6 pb-6 pt-2">
          <button type="button" wire:click="$set('showModal', false)"
            class="py-2.5 px-4 rounded-[10px] border border-[#e5e1de] text-[#697487] bg-white font-bold text-xs transition-colors hover:bg-[#faf9f8]">
            Cancelar
          </button>
          <button type="button" wire:click="guardar"
            class="inline-flex items-center gap-2 py-2.5 px-4 rounded-[11px] text-white bg-gradient-to-br from-brand-500 to-brand-700 shadow-[0_7px_16px_rgba(109,25,56,.18)] font-bold text-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_10px_20px_rgba(109,25,56,.23)]">
            <i data-lucide="check" class="w-4 h-4"></i>
            {{ $editingRoleId ? 'Guardar cambios' : 'Crear rol' }}
          </button>
        </div>
      </div>
    </div>
  @endif

  @script
  <script>
    // Re-inicializar iconos Lucide después de cada actualización de Livewire
    lucide.createIcons();
  </script>
  @endscript
</div>