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
    <div class="fixed inset-0 z-[1000] grid place-items-center p-5 bg-black/55 backdrop-blur-sm" wire:click.self="cerrarModal">
      <div class="w-full max-w-[560px] max-h-[calc(100vh-40px)] flex flex-col overflow-hidden rounded-[20px] bg-white shadow-2xl">
        <div class="shrink-0 flex items-start justify-between gap-4 px-6 pt-6 pb-4 border-b border-[#eeeae8]">
          <div>
            <h2 class="m-0 text-[#283348] text-lg">{{ $editingUserId ? 'Editar usuario' : 'Nuevo usuario' }}</h2>
            <p class="mt-1 mb-0 text-[#919aaa] text-[11px]">{{ $editingUserId ? 'Actualiza la información de la cuenta seleccionada.' : 'Registra una nueva cuenta de acceso en el sistema TESCHA.' }}</p>
          </div>
          <button type="button" wire:click="cerrarModal"
            class="grid place-items-center w-[34px] h-[34px] rounded-[9px] text-[#788395] bg-[#f7f5f3] transition-colors hover:text-brand-700 hover:bg-brand-100">
            <i data-lucide="x" class="w-4 h-4"></i>
          </button>
        </div>
        <div class="min-h-0 flex-1 overflow-y-auto p-6">
          @if($editingUserId)
            {{-- Edición: los campos personales dependen del rol seleccionado. --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                    @foreach($rolesEdicion as $rol)
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

            @if($rolConPersona)
              @if($rolOriginalNombre === 'Control Escolar')
                <div class="mt-3 flex items-start gap-2 p-3 rounded-[10px] bg-[#f6f4f2] border border-[#ece9e6] text-[#748095] text-[11px]">
                  <i data-lucide="info" class="w-4 h-4 shrink-0 text-brand-500"></i>
                  <span>Esta cuenta solo tiene un nombre completo. Se conserva como nombre de la cuenta; los nombres y apellidos del nuevo perfil deben capturarse por separado.</span>
                </div>
              @endif
              <div class="mt-4 mb-2 flex items-center gap-2 text-[#4b5769] text-[11px] font-extrabold uppercase tracking-[.06em]">
                <i data-lucide="user" class="w-3.5 h-3.5 text-brand-500"></i>
                Datos de la persona · {{ $rolElegido }}
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="mb-2">
                  <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Nombre(s) <span class="text-red-500">*</span></label>
                  <input type="text" wire:model="p_nombre" placeholder="Ej. María Fernanda"
                    class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
                  @error('p_nombre') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div class="mb-2">
                  <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Apellido paterno <span class="text-red-500">*</span></label>
                  <input type="text" wire:model="p_apellido_paterno" placeholder="Ej. Amaro"
                    class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
                  @error('p_apellido_paterno') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-2">
                <div class="mb-2">
                  <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Apellido materno</label>
                  <input type="text" wire:model="p_apellido_materno" placeholder="Ej. Colin"
                    class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
                  @error('p_apellido_materno') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div class="mb-2">
                  <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Correo electrónico <span class="text-red-500">*</span></label>
                  <input type="email" wire:model="email" placeholder="Ej. usuario@tescha.edu.mx"
                    class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
                  @error('email') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-2">
                <div class="mb-2">
                  @if($rolElegido === 'Alumno')
                    <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Número de control <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="no_control" placeholder="Ej. 202224001"
                      class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
                    @error('no_control') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                  @else
                    <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Número de empleado <span class="text-red-500">*</span></label>
                    <input type="text" wire:model.blur="no_empleado" placeholder="Ej. 202224001"
                      class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
                    @error('no_empleado') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                  @endif
                </div>

                @if($rolConCarrera)
                  <div class="mb-2">
                    <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Carrera <span class="text-red-500">*</span></label>
                    <select wire:model="id_carrera"
                      class="w-full bg-white py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
                      <option value="">— Selecciona una carrera —</option>
                      @foreach($carrerasEdicion as $carrera)
                        <option value="{{ $carrera['id_carrera'] }}" @selected($id_carrera == $carrera['id_carrera'])>
                          {{ $carrera['clave'] }} · {{ $carrera['nombre'] }}{{ ! $carrera['activo'] ? ' (Inactiva)' : '' }}
                        </option>
                      @endforeach
                    </select>
                    @error('id_carrera') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                  </div>
                @endif
              </div>

              @if($rolConMultiCarrera)
                <div class="mb-2 mt-2">
                  <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">
                    Carreras <span class="text-red-500">*</span>
                  </label>
                  <div class="max-h-[180px] overflow-auto p-3 rounded-[10px] border border-[#e3dfdc] bg-[#faf9f8] grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @forelse($carrerasEdicion as $carrera)
                      <label wire:key="editar-carrera-{{ $carrera['id_carrera'] }}" class="inline-flex items-start gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="carreras_ids" value="{{ $carrera['id_carrera'] }}"
                          class="w-4 h-4 mt-[1px] text-brand-500 rounded border-[#cfc9c5] focus:ring-brand-500/25 shrink-0">
                        <span class="text-[#293448] text-[12px] font-medium leading-[1.35]">
                          <b class="text-brand-700">{{ $carrera['clave'] }}</b> · {{ $carrera['nombre'] }}{{ ! $carrera['activo'] ? ' (Inactiva)' : '' }}
                        </span>
                      </label>
                    @empty
                      <span class="text-[#99a2b0] text-[12px]">No hay carreras disponibles.</span>
                    @endforelse
                  </div>
                  @php
                    $errCarrerasEdicion = $errors->first('carreras_ids');
                    if (! $errCarrerasEdicion) {
                        foreach ($errors->toArray() as $claveErrorEdicion => $mensajesEdicion) {
                            if (str_starts_with($claveErrorEdicion, 'carreras_ids.')) {
                                $errCarrerasEdicion = $mensajesEdicion[0] ?? null;
                                break;
                            }
                        }
                    }
                  @endphp
                  @if($errCarrerasEdicion)
                    <span class="text-red-500 text-xs mt-1 block">{{ $errCarrerasEdicion }}</span>
                  @endif
                </div>
              @endif

              @if($rolConFechaInicio)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-2">
                  <div class="mb-2">
                    <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Fecha de inicio <span class="text-red-500">*</span></label>
                    <input type="date" wire:model="fecha_inicio"
                      class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
                    @error('fecha_inicio') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                  </div>
                </div>
              @endif
            @else
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                <div class="mb-2">
                  <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Nombre completo <span class="text-red-500">*</span></label>
                  @if($esCuentaAdministrador)
                    <div class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] bg-[#faf9f8] text-[#293448] font-medium text-[13px]">
                      {{ $nombre }}
                    </div>
                  @else
                    <input type="text" wire:model="nombre" placeholder="Ej. María Fernanda Amaro"
                      class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
                    @error('nombre') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                  @endif
                </div>
                <div class="mb-2">
                  <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Correo electrónico <span class="text-red-500">*</span></label>
                  <input type="email" wire:model="email" placeholder="Ej. usuario@tescha.edu.mx"
                    class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
                  @error('email') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
              </div>
            @endif

            <p class="mt-3 mb-0 text-[#99a2b0] text-[11px] flex items-center gap-1.5">
              <i data-lucide="info" class="w-3.5 h-3.5"></i>
              La contraseña no se modifica aquí. Para cambiarla usa la acción "Restablecer contraseña" del listado.
            </p>

          @else
            {{-- ===================================================================
                 ALTA — el rol se elige primero porque determina los datos que
                 se capturan y si se creará (o vinculará) una persona.
                 =================================================================== --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="mb-2">
                <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Rol <span class="text-red-500">*</span></label>
                <select wire:model.live="rolId"
                  class="w-full bg-white py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
                  <option value="">— Selecciona un rol —</option>
                  @foreach($roles as $rol)
                    <option value="{{ $rol['id_rol'] }}" @selected($rolId == $rol['id_rol'])>{{ $rol['nombre'] }}</option>
                  @endforeach
                </select>
                @error('rolId') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
              </div>
              <div class="mb-2 flex items-end pb-2.5">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                  <input type="checkbox" wire:model="activo" class="w-4 h-4">
                  <span class="text-[#4b5769] text-[12px] font-extrabold">Cuenta activa</span>
                </label>
              </div>
            </div>
            @error('activo') <span class="text-red-500 text-xs block mb-2">{{ $message }}</span> @enderror

            @if($rolConPersona)
              {{-- ---------- Persona: Alumno / Docente / Jefe de Carrera ---------- --}}
              <div class="mt-4 mb-2 flex items-center gap-2 text-[#4b5769] text-[11px] font-extrabold uppercase tracking-[.06em]">
                <i data-lucide="user" class="w-3.5 h-3.5 text-brand-500"></i>
                Datos de la persona · {{ $rolElegido }}
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="mb-2">
                  <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Nombre(s) <span class="text-red-500">*</span></label>
                  <input type="text" wire:model="p_nombre" placeholder="Ej. María Fernanda"
                    class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
                  @error('p_nombre') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div class="mb-2">
                  <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Apellido paterno <span class="text-red-500">*</span></label>
                  <input type="text" wire:model="p_apellido_paterno" placeholder="Ej. Amaro"
                    class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
                  @error('p_apellido_paterno') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-2">
                <div class="mb-2">
                  <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Apellido materno</label>
                  <input type="text" wire:model="p_apellido_materno" placeholder="Ej. Colin"
                    class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
                  @error('p_apellido_materno') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div class="mb-2">
                  <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Correo electrónico <span class="text-red-500">*</span></label>
                  <input type="email" wire:model="email" placeholder="Ej. usuario@tescha.edu.mx"
                    class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
                  @error('email') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
              </div>

              {{-- Identificador propio de la entidad + carrera (si aplica) --}}
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-2">
                <div class="mb-2">
                  @if($rolElegido === 'Alumno')
                    <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Número de control <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="no_control" placeholder="Ej. 202224001"
                      class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
                    @error('no_control') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                  @else
                    <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Número de empleado <span class="text-red-500">*</span></label>
                    {{-- .blur: al salir del campo se evalúa si ese docente ya
                         existe y qué carreras tiene, para mostrarlas antes
                         de guardar. --}}
                    <input type="text" wire:model.blur="no_empleado" placeholder="Ej. 202224001"
                      class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
                    @error('no_empleado') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                  @endif
                </div>

                @if($rolConCarrera)
                  <div class="mb-2">
                    <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Carrera <span class="text-red-500">*</span></label>
                    <select wire:model="id_carrera"
                      class="w-full bg-white py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
                      <option value="">— Selecciona una carrera —</option>
                      @foreach($carreras as $carrera)
                        <option value="{{ $carrera['id_carrera'] }}" @selected($id_carrera == $carrera['id_carrera'])>
                          {{ $carrera['clave'] }} · {{ $carrera['nombre'] }}
                        </option>
                      @endforeach
                    </select>
                    @error('id_carrera') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                  </div>
                @endif
              </div>

              @if($esCreacion && $rolConMultiCarrera)
                {{-- ---------- Docente: una o varias carreras (N:N) ---------- --}}
                <div class="mb-2 mt-2">
                  <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">
                    Carreras <span class="text-red-500">*</span>
                    <span class="text-[#99a2b0] font-medium normal-case">(mínimo una)</span>
                  </label>
                  <div class="max-h-[180px] overflow-auto p-3 rounded-[10px] border border-[#e3dfdc] bg-[#faf9f8] grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @forelse($carreras as $carrera)
                      <label class="inline-flex items-start gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="carreras_ids" value="{{ $carrera['id_carrera'] }}"
                          class="w-4 h-4 mt-[1px] text-brand-500 rounded border-[#cfc9c5] focus:ring-brand-500/25 shrink-0">
                        <span class="text-[#293448] text-[12px] font-medium leading-[1.35]">
                          <b class="text-brand-700">{{ $carrera['clave'] }}</b> · {{ $carrera['nombre'] }}
                        </span>
                      </label>
                    @empty
                      <span class="text-[#99a2b0] text-[12px]">No hay carreras activas disponibles.</span>
                    @endforelse
                  </div>

                  {{-- Errores de carreras_ids (incluidos los de cada elemento) --}}
                  @php
                    $errCarreras = $errors->first('carreras_ids');
                    if (! $errCarreras) {
                        foreach ($errors->toArray() as $claveError => $mensajes) {
                            if (str_starts_with($claveError, 'carreras_ids.')) {
                                $errCarreras = $mensajes[0] ?? null;
                                break;
                            }
                        }
                    }
                  @endphp
                  @if($errCarreras)
                    <span class="text-red-500 text-xs mt-1 block">{{ $errCarreras }}</span>
                  @endif

                  {{-- Asignaciones actuales de un docente que ya existe --}}
                  @if($docentePreexistente)
                    <div class="mt-2 p-3 rounded-[10px] border border-amber-300 bg-amber-50">
                      <div class="flex items-start gap-2 text-[#8a5a12] text-[11px] font-bold">
                        <i data-lucide="alert-triangle" class="w-4 h-4 shrink-0 text-gold-500"></i>
                        <span><b>{{ $docentePreexistente['nombre'] }}</b> ya pertenece a:</span>
                      </div>
                      <div class="flex flex-wrap gap-x-4 gap-y-1.5 mt-2">
                        @foreach($docentePreexistente['carreras'] as $carr)
                          <span class="inline-flex items-center gap-1.5">
                            <span class="px-1.5 py-0.5 rounded text-white bg-brand-500 text-[10px] font-extrabold">{{ $carr['clave'] }}</span>
                            <span class="text-[#748095] text-[11px]">{{ $carr['nombre'] }}</span>
                          </span>
                        @endforeach
                      </div>
                      <label class="mt-2.5 flex items-start gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="confirmarCarreras"
                          class="w-4 h-4 mt-[1px] rounded border-[#cfc9c5] focus:ring-gold-500/25 shrink-0">
                        <span class="text-[#8a5a12] text-[11px] font-semibold leading-[1.4]">
                          Confirmo reemplazar las asignaciones anteriores por las carreras marcadas arriba.
                        </span>
                      </label>
                    </div>
                  @endif
                </div>
              @endif

              @if($rolConFechaInicio)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-2">
                  <div class="mb-2">
                    <label class="block mb-1.5 text-[#4b5769] text-[11px] font-extrabold">Fecha de inicio <span class="text-red-500">*</span></label>
                    <input type="date" wire:model="fecha_inicio"
                      class="w-full py-2.5 px-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] font-medium text-[13px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
                    @error('fecha_inicio') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                  </div>
                </div>
              @endif

            @else
              {{-- ---------- Sin entidad de persona (Control Escolar o rol sin elegir) ---------- --}}
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
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
            @endif

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

            <div class="mt-3 flex items-start gap-2 p-3 rounded-[10px] bg-[#f6f4f2] border border-[#ece9e6] text-[#748095] text-[11px]">
              <i data-lucide="info" class="w-4 h-4 shrink-0 text-brand-500"></i>
              <span>
                @if($rolConPersona)
                  Al guardar se creará el registro de <b class="text-[#293448]">{{ $rolElegido }}</b> y quedará vinculado a esta cuenta.
                  Si esa persona ya existe se vinculará sin duplicarla; si sus datos no coinciden, no se creará la cuenta y se te avisará.
                @elseif($rolElegido === '')
                  Selecciona un rol: si representa una persona (Alumno, Docente o Jefe de Carrera) deberás capturar además sus datos y el
                  sistema creará esa persona junto con la cuenta.
                @else
                  Este rol no tiene una entidad de persona asociada: solo se creará la cuenta de acceso.
                @endif
              </span>
            </div>
          @endif
        </div>
        <div class="shrink-0 flex justify-end gap-2 px-6 pb-6 pt-2 border-t border-[#eeeae8] mt-2">
          <button type="button" wire:click="cerrarModal"
            class="py-2.5 px-4 rounded-[10px] border border-[#e5e1de] text-[#697487] bg-white font-bold text-xs transition-colors hover:bg-[#faf9f8]">
            Cancelar
          </button>
          <button type="button" wire:click="guardar"
            class="inline-flex items-center gap-2 py-2.5 px-4 rounded-[11px] text-white bg-gradient-to-br from-brand-500 to-brand-700 shadow-[0_7px_16px_rgba(109,25,56,.18)] font-bold text-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_10px_20px_rgba(109,25,56,.23)]">
            <i data-lucide="check" class="w-4 h-4"></i>
            {{ $editingUserId ? 'Guardar cambios' : 'Crear usuario' }}
          </button>
        </div>

        @if($mostrarConfirmacionGuardado)
          <div class="fixed inset-0 z-[1200] grid place-items-center p-5 bg-black/55 backdrop-blur-sm">
            <div class="w-full max-w-[460px] rounded-[16px] bg-white shadow-2xl p-5">
              <div class="flex items-start gap-3">
                <i data-lucide="triangle-alert" class="w-5 h-5 shrink-0 text-amber-600 mt-0.5"></i>
                <div>
                  <h3 class="m-0 text-[#283348] text-[15px] font-extrabold">Confirma los cambios</h3>
                  @if($rolOriginalNombre !== $rolElegido)
                    <p class="mt-1.5 mb-0 text-[#748095] text-[12px]">
                      Estás cambiando el rol de <b>{{ $rolOriginalNombre }}</b> a <b>{{ $rolElegido }}</b>. Esto actualizará los permisos de acceso de la cuenta.
                    </p>
                    <p class="mt-2 mb-0 text-[#748095] text-[12px]">
                      Los registros y relaciones de la persona anterior se conservarán.
                      @if($rolConPersona)
                        El perfil del nuevo rol se vinculará si existe una coincidencia válida o se creará con los datos capturados.
                      @else
                        Este rol no tiene un registro de persona asociado; los vínculos anteriores se conservarán sin cambios.
                      @endif
                      La contraseña no cambiará.
                    </p>
                  @else
                    <p class="mt-1.5 mb-0 text-[#748095] text-[12px]">Se actualizarán las carreras vinculadas al perfil Docente seleccionado.</p>
                  @endif

                  @if($carrerasAgregadasConfirmacion !== [])
                    <p class="mt-2 mb-0 text-[#748095] text-[12px]">Se agregarán: <b>{{ implode(', ', $carrerasAgregadasConfirmacion) }}</b>.</p>
                  @endif
                  @if($carrerasRetiradasConfirmacion !== [])
                    <p class="mt-1 mb-0 text-[#748095] text-[12px]">Se retirarán de ese perfil: <b>{{ implode(', ', $carrerasRetiradasConfirmacion) }}</b>. Las demás relaciones académicas permanecerán intactas.</p>
                  @endif
                </div>
              </div>
              <div class="flex justify-end gap-2 mt-4">
                <button type="button" wire:click="cancelarGuardadoPendiente"
                  class="py-2 px-4 rounded-[10px] border border-[#e5e1de] text-[#697487] bg-white font-bold text-xs transition-colors hover:bg-[#faf9f8]">
                  Volver al formulario
                </button>
                <button type="button" wire:click="confirmarGuardadoPendiente"
                  class="py-2 px-4 rounded-[10px] text-white bg-gradient-to-br from-brand-500 to-brand-700 font-bold text-xs transition-colors hover:opacity-90">
                  Confirmar y guardar
                </button>
              </div>
            </div>
          </div>
        @endif

        {{-- Aviso de cierre sin guardar (solo EDICION con cambios):
             aparece sobre el modal cuando se intenta cerrar la X,
             "Cancelar" o el fondo, y aun no se ha guardado. --}}
        @if($confirmarCierrePendiente)
          <div class="fixed inset-0 z-[1200] grid place-items-center p-5 bg-black/55 backdrop-blur-sm">
            <div class="w-full max-w-[420px] rounded-[16px] bg-white shadow-2xl p-5">
              <div class="flex items-start gap-3">
                <i data-lucide="triangle-alert" class="w-5 h-5 shrink-0 text-amber-600 mt-0.5"></i>
                <div>
                  <h3 class="m-0 text-[#283348] text-[15px] font-extrabold">Hay cambios sin guardar</h3>
                  <p class="mt-1.5 mb-0 text-[#748095] text-[12px]">Si cierras ahora, los cambios realizados en este usuario se perderán.</p>
                </div>
              </div>
              <div class="flex justify-end gap-2 mt-4">
                <button type="button" wire:click="cancelarCierreConfirmado"
                  class="py-2 px-4 rounded-[10px] border border-[#e5e1de] text-[#697487] bg-white font-bold text-xs transition-colors hover:bg-[#faf9f8]">
                  Seguir editando
                </button>
                <button type="button" wire:click="cerrarModalSinGuardar"
                  class="py-2 px-4 rounded-[10px] border border-red-200 text-red-600 bg-red-50 font-bold text-xs transition-colors hover:bg-red-100">
                  Cerrar sin guardar
                </button>
              </div>
            </div>
          </div>
        @endif
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
