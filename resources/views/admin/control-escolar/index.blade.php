<section class="space-y-5">
  <header class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
    <div>
      <h1 class="m-0 text-[#202b3d] text-[26px] md:text-[29px] tracking-tight">Personal de Control Escolar</h1>
      <p class="mt-1.5 mb-0 text-[#748095] text-sm">Consulta las cuentas asignadas al rol Control Escolar y administra sus datos con Usuarios y Roles.</p>
    </div>
    <button type="button" onclick="crearUsuarioControlEscolarDesdeDashboard()"
      class="inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-[11px] text-white bg-gradient-to-br from-brand-500 to-brand-700 shadow-[0_7px_16px_rgba(109,25,56,.18)] font-bold text-xs transition-all hover:-translate-y-0.5">
      <i data-lucide="user-plus" class="w-4 h-4"></i>
      Crear personal
    </button>
  </header>

  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <article class="p-5 rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
      <span class="text-[#8993a4] text-[11px] font-extrabold uppercase tracking-[.06em]">Personal registrado</span>
      <b class="block mt-2 text-[#293448] text-2xl">{{ number_format($totalPersonal) }}</b>
      <span class="block mt-1 text-[#98a2b2] text-[11px]">Cuentas con el rol Control Escolar</span>
    </article>
    <article class="p-5 rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
      <span class="text-[#8993a4] text-[11px] font-extrabold uppercase tracking-[.06em]">Cuentas activas</span>
      <b class="block mt-2 text-[#293448] text-2xl">{{ number_format($cuentasActivas) }}</b>
      <span class="block mt-1 text-[#98a2b2] text-[11px]">Pueden iniciar sesión en el sistema</span>
    </article>
  </div>

  @if(session()->has('success'))
    <div class="p-3 rounded-[10px] border border-emerald-200 bg-emerald-50 text-emerald-800 text-[12px]">
      {{ session('success') }}
    </div>
  @endif

  <section class="overflow-hidden rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_5px_13px_rgba(31,38,50,.04)]">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 py-4 border-b border-[#f1efed]">
      <div>
        <h2 class="m-0 text-[#293448] text-[16px]">Personal registrado</h2>
        <p class="mt-1 mb-0 text-[#8993a4] text-[11px]">Las acciones de cuenta se realizan mediante el módulo existente Usuarios y Roles.</p>
      </div>
      <label class="relative w-full sm:max-w-[300px]">
        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#98a2b2]"></i>
        <input type="search" wire:model.live.debounce.300ms="busqueda" placeholder="Buscar por nombre o correo..."
          class="w-full py-2.5 pl-9 pr-3 rounded-[10px] border border-[#e3dfdc] outline-none text-[#293448] text-[12px] focus:border-[#b97a91] focus:ring-4 focus:ring-brand-500/10">
      </label>
    </div>

    @if($usuarios->isEmpty())
      <div class="px-5 py-12 text-center">
        <div class="grid place-items-center w-[52px] h-[52px] mx-auto mb-4 rounded-2xl text-[#98a2b2] bg-cream">
          <i data-lucide="users-round" class="w-[24px]"></i>
        </div>
        <p class="m-0 text-[#748095] text-[13px]">
          {{ trim($busqueda) === '' ? 'No hay personal asignado al rol Control Escolar.' : 'No se encontraron coincidencias.' }}
        </p>
      </div>
    @else
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-[#fafafa] text-[#748095] text-[10px] uppercase tracking-[.06em]">
              <th class="px-5 py-3 font-extrabold">Nombre</th>
              <th class="px-5 py-3 font-extrabold">Correo electrónico</th>
              <th class="px-5 py-3 font-extrabold">Rol</th>
              <th class="px-5 py-3 font-extrabold">Estado</th>
              <th class="px-5 py-3 font-extrabold text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="text-[#354156] text-[12px]">
            @foreach($usuarios as $usuario)
              <tr wire:key="personal-control-escolar-{{ $usuario->id }}" class="border-t border-cream hover:bg-[#fcfafb]">
                <td class="px-5 py-3.5"><b>{{ $usuario->name }}</b></td>
                <td class="px-5 py-3.5">{{ $usuario->email }}</td>
                <td class="px-5 py-3.5">{{ $usuario->role?->nombre ?? '—' }}</td>
                <td class="px-5 py-3.5">
                  <span class="inline-flex px-2 py-1 rounded-full text-[10px] font-extrabold {{ $usuario->activo ? 'text-emerald-700 bg-emerald-50' : 'text-[#748095] bg-[#f1efed]' }}">
                    {{ $usuario->activo ? 'Activa' : 'Inactiva' }}
                  </span>
                </td>
                <td class="px-5 py-3.5">
                  <div class="flex justify-end items-center gap-1.5">
                    <button type="button" wire:click="verDetalles({{ $usuario->id }})" title="Consultar detalles"
                      class="grid place-items-center w-8 h-8 rounded-lg text-[#748095] hover:text-brand-700 hover:bg-brand-50">
                      <i data-lucide="eye" class="w-4 h-4"></i>
                    </button>
                    <button type="button" onclick="editarUsuarioControlEscolarDesdeDashboard({{ $usuario->id }})" title="Editar usuario"
                      class="grid place-items-center w-8 h-8 rounded-lg text-[#748095] hover:text-brand-700 hover:bg-brand-50">
                      <i data-lucide="pencil" class="w-4 h-4"></i>
                    </button>
                    @if((int) $usuarioAutenticadoId !== (int) $usuario->id)
                      <button type="button" wire:click="cambiarEstado({{ $usuario->id }})"
                        wire:confirm="{{ $usuario->activo ? '¿Desactivar esta cuenta de Control Escolar?' : '¿Reactivar esta cuenta de Control Escolar?' }}"
                        title="{{ $usuario->activo ? 'Desactivar cuenta' : 'Reactivar cuenta' }}"
                        class="grid place-items-center w-8 h-8 rounded-lg {{ $usuario->activo ? 'text-red-500 hover:bg-red-50' : 'text-emerald-600 hover:bg-emerald-50' }}">
                        <i data-lucide="{{ $usuario->activo ? 'user-round-x' : 'user-round-check' }}" class="w-4 h-4"></i>
                      </button>
                    @endif
                  </div>
                </td>
              </tr>

              @if($usuarioDetalle && (int) $usuarioDetalle->id === (int) $usuario->id)
                <tr wire:key="personal-control-escolar-detalle-{{ $usuario->id }}" class="border-t border-[#f1efed] bg-[#fcfafb]">
                  <td colspan="5" class="px-5 py-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3 text-[11px]">
                      <div><span class="block text-[#98a2b2]">ID de cuenta</span><b class="text-[#354156]">{{ $usuarioDetalle->id }}</b></div>
                      <div><span class="block text-[#98a2b2]">Correo verificado</span><b class="text-[#354156]">{{ $usuarioDetalle->email_verified_at?->format('d/m/Y H:i') ?? 'No verificado' }}</b></div>
                      <div><span class="block text-[#98a2b2]">Cuenta creada</span><b class="text-[#354156]">{{ $usuarioDetalle->created_at?->format('d/m/Y H:i') ?? '—' }}</b></div>
                      <div><span class="block text-[#98a2b2]">Última actualización</span><b class="text-[#354156]">{{ $usuarioDetalle->updated_at?->format('d/m/Y H:i') ?? '—' }}</b></div>
                    </div>
                  </td>
                </tr>
              @endif
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="px-5 py-4 border-t border-[#f1efed]">{{ $usuarios->links() }}</div>
    @endif
  </section>
</section>
