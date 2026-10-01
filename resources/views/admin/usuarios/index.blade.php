<div>
  <!-- Header -->
  <h1 class="m-0 text-[#202b3d] text-[26px] md:text-[29px] tracking-tight">Gestión de usuarios</h1>
  <p class="mt-1.5 mb-6 text-[#748095] text-sm">Administra las cuentas de acceso al sistema TESCHA: nombre, correo, rol y estado de cada cuenta.</p>

  <!-- Barra de búsqueda y botón -->
  <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 p-4 rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_8px_22px_rgba(31,38,50,.045)]">
    <div class="flex items-center gap-2.5 flex-1 sm:max-w-[430px] px-3.5 py-2.5 rounded-[11px] border border-[#e8e4e1] bg-[#faf9f8] text-[#8b95a5]">
      <i data-lucide="search" class="w-[17px]"></i>
      <input type="search" wire:model.live="search" placeholder="Buscar usuario por nombre, correo o rol..." class="w-full border-0 outline-none bg-transparent text-[#253146] text-[13px] font-medium">
    </div>
    <button type="button" wire:click="abrirModalCrear"
      class="inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-[11px] text-white bg-gradient-to-br from-brand-500 to-brand-700 shadow-[0_7px_16px_rgba(109,25,56,.18)] text-[13px] font-bold transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_10px_20px_rgba(109,25,56,.23)]">
      <i data-lucide="plus" class="w-4 h-4"></i>
      Nuevo usuario
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

  <!-- Estadísticas rápidas -->
  <section class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
    <div class="p-4 rounded-[15px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
      <div class="flex items-center gap-3">
        <div class="grid place-items-center w-10 h-10 rounded-xl text-brand-500 bg-brand-100">
          <i data-lucide="users" class="w-5 h-5"></i>
        </div>
        <div>
          <b class="block text-[#293448] text-lg">{{ $userCount }}</b>
          <span class="text-[#919aaa] text-[11px]">Total cuentas</span>
        </div>
      </div>
    </div>
    <div class="p-4 rounded-[15px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
      <div class="flex items-center gap-3">
        <div class="grid place-items-center w-10 h-10 rounded-xl text-emerald-600 bg-emerald-50">
          <i data-lucide="user-check" class="w-5 h-5"></i>
        </div>
        <div>
          <b class="block text-[#293448] text-lg">{{ collect($usuarios)->where('activo', true)->count() }}</b>
          <span class="text-[#919aaa] text-[11px]">Activas</span>
        </div>
      </div>
    </div>
    <div class="p-4 rounded-[15px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
      <div class="flex items-center gap-3">
        <div class="grid place-items-center w-10 h-10 rounded-xl text-gold-500 bg-amber-50">
          <i data-lucide="user-x" class="w-5 h-5"></i>
        </div>
        <div>
          <b class="block text-[#293448] text-lg">{{ collect($usuarios)->where('activo', false)->count() }}</b>
          <span class="text-[#919aaa] text-[11px]">Inactivas</span>
        </div>
      </div>
    </div>
  </section>

  <!-- Tabla de cuentas de usuario -->
  <section class="mt-4 overflow-hidden rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_8px_22px_rgba(31,38,50,.045)]">
    <div class="flex items-center justify-between px-5 py-5 border-b border-[#efedeb]">
      <div>
        <h2 class="m-0 text-[#293448] text-base">Catálogo de cuentas de usuario</h2>
        <span class="text-[#919aaa] text-[11px]">Cuentas de acceso registradas en el sistema TESCHA</span>
      </div>
      <span class="text-[#919aaa] text-[11px]">{{ $userCount }} cuentas listadas</span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full border-collapse min-w-[860px]">
        <thead>
          <tr>
            <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Usuario</th>
            <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Rol</th>
            <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Persona vinculada</th>
            <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Estatus</th>
            <th class="px-5 py-3 text-[#7d8797] bg-[#fcfaf9] border-b border-[#ece9e6] text-[10px] text-left uppercase tracking-[.07em]">Acciones</th>
          </tr>
        </thead>
        <tbody>
          @forelse($usuarios as $u)
            @php $esAdmin = $u['rol_nombre'] === 'Administrador'; @endphp
            <tr class="transition-colors duration-150 hover:bg-[#fcfafb]">
              <td class="px-5 py-4 border-b border-[#f2efed]">
                <div class="flex items-center gap-3 min-w-[220px]">
                  <div class="grid place-items-center w-10 h-10 shrink-0 rounded-xl {{ $esAdmin ? 'text-gold-500 bg-amber-50' : 'text-brand-500 bg-brand-100' }}">
                    <i data-lucide="user" class="w-[19px] h-[19px]"></i>
                  </div>
                  <div>
                    <span class="block text-[#263247] font-extrabold text-[13px]">
                      {{ $u['name'] }}
                      @if($u['es_cuenta_propia'])
                        <span class="ml-1 align-middle text-[9px] font-extrabold text-brand-600 bg-brand-100 px-1.5 py-0.5 rounded">TU CUENTA</span>
                      @endif
                    </span>
                    <span class="block mt-0.5 text-[#99a2b0] text-[11px]">{{ $u['email'] }}</span>
                  </div>
                </div>
              </td>
              <td class="px-5 py-4 border-b border-[#f2efed]">
                <span class="inline-flex items-center gap-2 text-[#39465a] text-[13px] font-bold">
                  <i data-lucide="{{ $esAdmin ? 'shield-check' : 'shield' }}" class="w-4 h-4 {{ $esAdmin ? 'text-gold-500' : 'text-brand-500' }}"></i>
                  {{ $u['rol_nombre'] }}
                </span>
              </td>
              <td class="px-5 py-4 border-b border-[#f2efed]">
                @if($u['persona_texto'])
                  <span class="block text-[#39465a] text-[13px]">{{ $u['persona_texto'] }}</span>
                @else
                  <span class="text-[#99a2b0] text-[13px]">Sin persona vinculada</span>
                @endif
              </td>
              <td class="px-5 py-4 border-b border-[#f2efed]">
                @if($u['activo'])
                  <span class="inline-flex items-center gap-1.5 text-emerald-700 text-[11px] font-bold">
                    <span class="w-[7px] h-[7px] rounded-full bg-emerald-500 inline-block"></span>
                    Activo
                  </span>
                @else
                  <span class="inline-flex items-center gap-1.5 text-[#99a2b0] text-[11px] font-bold">
                    <span class="w-[7px] h-[7px] rounded-full bg-[#c7cbd2] inline-block"></span>
                    Inactivo
                  </span>
                @endif
              </td>
              <td class="px-5 py-4 border-b border-[#f2efed]">
                <div class="flex gap-1.5">
                  <button wire:click="abrirModalEditar({{ $u['id'] }})" title="Editar usuario"
                    class="grid place-items-center w-[34px] h-[34px] rounded-[9px] border border-[#e9e5e2] text-[#687488] bg-white transition-all duration-200 hover:text-brand-600 hover:border-[#d9bcc7] hover:bg-[#fff9fb] hover:scale-110">
                    <i data-lucide="pencil" class="w-4 h-4"></i>
                  </button>
                  <button wire:click="abrirModalPassword({{ $u['id'] }})" title="Restablecer contraseña"
                    class="grid place-items-center w-[34px] h-[34px] rounded-[9px] border border-[#e9e5e2] text-[#687488] bg-white transition-all duration-200 hover:text-gold-500 hover:border-[#e7d6a9] hover:bg-[#fffdf7] hover:scale-110">
                    <i data-lucide="key-round" class="w-4 h-4"></i>
                  </button>
                  @if($u['es_cuenta_propia'])
                    <button disabled title="No puedes desactivar tu propia cuenta"
                      class="grid place-items-center w-[34px] h-[34px] rounded-[9px] border border-[#e9e5e2] text-[#c7cbd2] bg-[#fafafa] cursor-not-allowed">
                      <i data-lucide="lock" class="w-4 h-4"></i>
                    </button>
                  @else
                    <button wire:click="toggleActivo({{ $u['id'] }})"
                      wire:confirm="{{ $u['activo'] ? '¿Desactivar esta cuenta? El usuario ya no podrá iniciar sesión.' : '¿Reactivar esta cuenta?' }}"
                      title="{{ $u['activo'] ? 'Desactivar cuenta' : 'Reactivar cuenta' }}"
                      class="grid place-items-center w-[34px] h-[34px] rounded-[9px] border border-[#e9e5e2] text-[#687488] bg-white transition-all duration-200 {{ $u['activo'] ? 'hover:text-red-600 hover:border-red-200 hover:bg-red-50' : 'hover:text-emerald-600 hover:border-emerald-200 hover:bg-emerald-50' }} hover:scale-110">
                      <i data-lucide="{{ $u['activo'] ? 'user-x' : 'user-check' }}" class="w-4 h-4"></i>
                    </button>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="p-9 text-center text-[#8993a4] text-[13px]">
                <div class="flex flex-col items-center gap-3">
                  <i data-lucide="user-x" class="w-10 h-10 text-[#c1c6cf]"></i>
                  <span>No se encontraron usuarios con esa búsqueda.</span>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>

  <!-- Modal Crear / Editar Usuario -->
  @if($showModal)
    <div class="fixed inset-0 z-[1000] grid place-items-center p-5 bg-black/55 backdrop-blur-sm" wire:click.self="$set('showModal', false)">
      <div class="w-full max-w-[560px] max-h-[calc(100vh-40px)] overflow-auto rounded-[20px] bg-white shadow-2xl">
        <div class="flex items-start justify-between gap-4 px-6 pt-6 pb-4 border-b border-[#eeeae8]">
          <div>
            <h2 class="m-0 text-[#283348] text-lg">{{ $editingUserId ? 'Editar usuario' : 'Nuevo usuario' }}</h2>
            <p class="mt-1 mb-0 text-[#919aaa] text-[11px]">{{ $editingUserId ? 'Actualiza la información de la cuenta seleccionada.' : 'Registra una nueva cuenta de acceso en el sistema TESCHA.' }}</p>
          </div>
          <button type="button" wire:click="$set('showModal', false)"
            class="grid place-items-center w-[34px] h-[34px] rounded-[9px] text-[#788395] bg-[#f7f5f3] transition-colors hover:text-brand-700 hover:bg-brand-100">
            <i data-lucide="x" class="w-4 h-4"></i>
          </button>
        </div>
        <div class="p-6">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="mb-2">
              <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Nombre completo <span class="text-red-500">*</span></label>
              <input type="text" wire:model="nombre" placeholder="Ej. María Fernanda Amaro"
                class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
              @error('nombre') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>
            <div class="mb-2">
              <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Correo electrónico <span class="text-red-500">*</span></label>
              <input type="email" wire:model="email" placeholder="Ej. usuario@tescha.edu.mx"
                class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
              @error('email') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>
          </div>

          @if(! $editingUserId)
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-2">
              <div class="mb-2">
                <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Contraseña <span class="text-red-500">*</span></label>
                <input type="password" wire:model="password" placeholder="Mínimo 8 caracteres" autocomplete="new-password"
                  class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
                @error('password') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
              </div>
              <div class="mb-2">
                <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Confirmar contraseña <span class="text-red-500">*</span></label>
                <input type="password" wire:model="password_confirmation" placeholder="Repite la contraseña" autocomplete="new-password"
                  class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
                @error('password_confirmation') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
              </div>
            </div>
          @else
            <p class="mt-2 mb-0 text-[#99a2b0] text-[11px] flex items-center gap-1.5">
              <i data-lucide="info" class="w-3.5 h-3.5"></i>
              La contraseña no se muestra aquí. Para cambiarla usa la acción "Restablecer contraseña" del listado.
            </p>
          @endif

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
            <div class="mb-2">
              <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Rol <span class="text-red-500">*</span></label>
              @if($esCuentaAdministrador)
                {{-- Solo existe una cuenta Administrador: el rol se muestra en
                     solo lectura (no se puede elegir ni asignar a otra cuenta). --}}
                <div class="w-full flex items-center gap-2 py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] bg-[#faf9f8] text-[#293448] font-medium text-[13px]">
                  <i data-lucide="shield-check" class="w-4 h-4 shrink-0 text-gold-500"></i>
                  <span>Administrador</span>
                  <span class="ml-auto text-[9px] font-extrabold text-gold-600 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded">ROL ÚNICO</span>
                </div>
                <p class="mt-1.5 mb-0 text-[#99a2b0] text-[11px]">Solo existe una cuenta Administrador en el sistema: su rol no puede cambiarse ni asignarse a otra cuenta.</p>
              @else
                <select wire:model.live="rolId"
                  class="w-full bg-white py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
                  <option value="">— Selecciona un rol —</option>
                  @foreach($roles as $rol)
                    <option value="{{ $rol['id_rol'] }}" @selected($rolId == $rol['id_rol'])>{{ $rol['nombre'] }}</option>
                  @endforeach
                </select>
                @error('rolId') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
              @endif
            </div>
            <div class="mb-2 flex items-end pb-2.5">
              <label class="inline-flex items-center gap-2 cursor-pointer">
                <input type="checkbox" wire:model="activo" class="w-4 h-4">
                <span class="text-[#4b5769] text-[12px] font-extrabold">Cuenta activa</span>
              </label>
            </div>
          </div>
          @error('activo') <span class="text-red-500 text-xs block">{{ $message }}</span> @enderror

          {{-- Este modulo solo gestiona cuentas: no crea ni vincula personas --}}
          <div class="mt-3 flex items-start gap-2 p-3 rounded-[10px] bg-[#f6f4f2] border border-[#ece9e6] text-[#748095] text-[11px]">
            <i data-lucide="info" class="w-4 h-4 shrink-0 text-brand-500"></i>
            <span>Este módulo solo gestiona cuentas de acceso: no crea ni vincula personas. Los vínculos existentes con docentes, jefes o alumnos (si los hay) se conservan sin cambios.</span>
          </div>
        </div>
        <div class="flex justify-end gap-2 px-6 pb-6 pt-2 border-t border-[#eeeae8] mt-2">
          <button type="button" wire:click="$set('showModal', false)"
            class="py-2.5 px-4 rounded-[10px] border border-[#e5e1de] text-[#697487] bg-white font-bold text-xs transition-colors hover:bg-[#faf9f8]">
            Cancelar
          </button>
          <button type="button" wire:click="guardar"
            class="inline-flex items-center gap-2 py-2.5 px-4 rounded-[11px] text-white bg-gradient-to-br from-brand-500 to-brand-700 shadow-[0_7px_16px_rgba(109,25,56,.18)] font-bold text-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_10px_20px_rgba(109,25,56,.23)]">
            <i data-lucide="check" class="w-4 h-4"></i>
            {{ $editingUserId ? 'Guardar cambios' : 'Crear usuario' }}
          </button>
        </div>
      </div>
    </div>
  @endif

  <!-- Modal Restablecer Contraseña -->
  @if($showPasswordModal)
    <div class="fixed inset-0 z-[1000] grid place-items-center p-5 bg-black/55 backdrop-blur-sm" wire:click.self="$set('showPasswordModal', false)">
      <div class="w-full max-w-[460px] max-h-[calc(100vh-40px)] overflow-auto rounded-[20px] bg-white shadow-2xl">
        <div class="flex items-start justify-between gap-4 px-6 pt-6 pb-4 border-b border-[#eeeae8]">
          <div>
            <h2 class="m-0 text-[#283348] text-lg">Restablecer contraseña</h2>
            <p class="mt-1 mb-0 text-[#919aaa] text-[11px]">Define una nueva contraseña para la cuenta seleccionada. La contraseña actual no se muestra.</p>
          </div>
          <button type="button" wire:click="$set('showPasswordModal', false)"
            class="grid place-items-center w-[34px] h-[34px] rounded-[9px] text-[#788395] bg-[#f7f5f3] transition-colors hover:text-brand-700 hover:bg-brand-100">
            <i data-lucide="x" class="w-4 h-4"></i>
          </button>
        </div>
        <div class="p-6">
          <div class="mb-4">
            <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Nueva contraseña <span class="text-red-500">*</span></label>
            <input type="password" wire:model="nuevaPassword" placeholder="Mínimo 8 caracteres" autocomplete="new-password"
              class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
            @error('nuevaPassword') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
          </div>
          <div class="mb-2">
            <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Confirmar nueva contraseña <span class="text-red-500">*</span></label>
            <input type="password" wire:model="confirmarPassword" placeholder="Repite la nueva contraseña" autocomplete="new-password"
              class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
            @error('confirmarPassword') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
          </div>
        </div>
        <div class="flex justify-end gap-2 px-6 pb-6 pt-2">
          <button type="button" wire:click="$set('showPasswordModal', false)"
            class="py-2.5 px-4 rounded-[10px] border border-[#e5e1de] text-[#697487] bg-white font-bold text-xs transition-colors hover:bg-[#faf9f8]">
            Cancelar
          </button>
          <button type="button" wire:click="guardarPassword"
            class="inline-flex items-center gap-2 py-2.5 px-4 rounded-[11px] text-white bg-gradient-to-br from-brand-500 to-brand-700 shadow-[0_7px_16px_rgba(109,25,56,.18)] font-bold text-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_10px_20px_rgba(109,25,56,.23)]">
            <i data-lucide="key-round" class="w-4 h-4"></i>
            Guardar contraseña
          </button>
        </div>
      </div>
    </div>
  @endif
</div>
