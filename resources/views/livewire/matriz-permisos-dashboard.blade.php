<div>
  <!-- Header -->
  <h1 class="m-0 text-[#202b3d] text-[26px] md:text-[29px] tracking-tight">Matriz de permisos</h1>
  <p class="mt-1.5 mb-6 text-[#748095] text-sm">Marca o desmarca los permisos de cada rol por módulo del sistema TESCHA.</p>

  <!-- Mensajes flash (se ocultan automáticamente en 3s) -->
  @if(session('success'))
    <div class="flash-msg mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-[11px] text-sm font-semibold shadow-sm flex items-center gap-2">
      <i data-lucide="check-circle" class="w-4 h-4 text-emerald-500"></i>
      {{ session('success') }}
    </div>
  @endif

  @if(session('error'))
    <div class="flash-msg mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-[11px] text-sm font-semibold shadow-sm flex items-center gap-2">
      <i data-lucide="alert-circle" class="w-4 h-4 text-red-500"></i>
      {{ session('error') }}
    </div>
  @endif

  <!-- Matriz -->
  <div class="rounded-[17px] border border-[#ece9e6] bg-white shadow-[0_8px_22px_rgba(31,38,50,.045)] overflow-hidden">
    <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-[#efedeb]">
      <div class="flex items-center gap-2.5">
        <div class="grid place-items-center w-9 h-9 rounded-[10px] text-brand-500 bg-brand-100">
          <i data-lucide="grid-3x3" class="w-[18px] h-[18px]"></i>
        </div>
        <div>
          <h2 class="m-0 text-[#293448] text-[15px]">Módulos y roles</h2>
          <span class="text-[#919aaa] text-[11px]">Filas: módulos del sistema · Columnas: roles</span>
        </div>
      </div>
      <span class="hidden sm:flex items-center gap-1.5 text-[#9aa3b1] text-[10px] font-bold">
        <i data-lucide="lock" class="w-3 h-3"></i>Administrador con acceso fijo
      </span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full border-collapse text-sm min-w-[640px]">
        <thead>
          <tr>
            <th class="sticky left-0 z-10 px-5 py-3.5 text-left bg-[#fcfaf9] border-b border-r border-[#ece9e6] text-[10px] text-[#7d8797] uppercase tracking-[.06em] min-w-[210px]">Módulo</th>
            @foreach($roles as $role)
              <th class="px-3 py-3.5 bg-[#fcfaf9] border-b border-[#ece9e6] text-center min-w-[110px]">
                <div class="flex flex-col items-center gap-1.5">
                  <div class="grid place-items-center w-8 h-8 rounded-full text-white {{ $role['es_admin'] ? 'bg-gold-500' : 'bg-brand-500' }}">
                    <i data-lucide="{{ $role['icono'] }}" class="w-4 h-4"></i>
                  </div>
                  <span class="text-[#293448] text-[11px] font-extrabold leading-tight">{{ $role['nombre'] }}</span>
                  @if($role['es_admin'])
                    <span class="flex items-center gap-1 text-[9px] font-bold text-gold-500">
                      <i data-lucide="lock" class="w-2.5 h-2.5"></i>fijo
                    </span>
                  @endif
                </div>
              </th>
            @endforeach
          </tr>
        </thead>
        <tbody>
          @foreach($modulos as $modulo => $permisosList)
            <!-- Separador de Módulo -->
            <tr>
              <td colspan="{{ count($roles) + 1 }}" class="sticky left-0 bg-[#faf8f7] px-5 py-2 border-b border-[#f1efed]">
                <div class="flex items-center gap-2 text-brand-700 text-[11px] font-extrabold uppercase tracking-[.05em]">
                  <i data-lucide="layers" class="w-3.5 h-3.5"></i>
                  {{ $modulo ?: 'General' }}
                </div>
              </td>
            </tr>

            @foreach($permisosList as $permiso)
              <tr class="hover:bg-[#fcfafb] transition-colors duration-150">
                <td class="sticky left-0 z-10 bg-white px-5 py-3 border-b border-r border-[#f1efed] min-w-[210px]">
                  <strong class="block text-[#354156] text-xs">{{ $permiso['descripcion'] ?? $permiso['accion'] . ' ' . $modulo }}</strong>
                  <span class="block mt-0.5 text-[#9aa3b1] text-[10px]">{{ $permiso['accion'] }}</span>
                </td>

                @foreach($roles as $role)
                  @php
                    $isAdmin = $role['es_admin'];
                    $checked = $this->matriz[$role['id_rol']][$permiso['id_permiso']] ?? false;
                  @endphp
                  <td class="px-3 py-3 border-b border-[#f1efed] text-center transition-colors duration-300">
                    @if($isAdmin)
                      <!-- Admin: checkbox marcado y deshabilitado -->
                      <input type="checkbox" checked disabled
                        class="w-[18px] h-[18px] rounded border-2 border-[#d9dde3] opacity-60 cursor-not-allowed accent-gold-500"
                        title="El rol Administrador siempre tiene acceso total">
                    @else
                      <input type="checkbox"
                        wire:model.live="matriz.{{ $role['id_rol'] }}.{{ $permiso['id_permiso'] }}"
                        class="w-[18px] h-[18px] rounded border-2 border-[#d9dde3] cursor-pointer transition-transform duration-150 hover:scale-125">
                    @endif
                  </td>
                @endforeach
              </tr>
            @endforeach
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <!-- Botones -->
  <div class="flex items-center justify-end gap-2.5 mt-4 py-4 px-5 rounded-2xl border border-[#ece9e6] bg-white">
    <a href="{{ route('dashboard') }}"
      class="py-2.5 px-4 rounded-[10px] border border-[#e5e1de] text-[#697487] bg-white font-bold text-xs transition-colors hover:bg-[#faf9f8] no-underline">
      Volver al catálogo
    </a>
    <button type="button" wire:click="guardar" wire:loading.attr="disabled"
      class="inline-flex items-center gap-2 py-2.5 px-4 rounded-[11px] text-white bg-gradient-to-br from-brand-500 to-brand-700 shadow-[0_7px_16px_rgba(109,25,56,.18)] font-bold text-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_10px_20px_rgba(109,25,56,.23)] disabled:opacity-50">
      <span wire:loading.remove wire:target="guardar">
        <i data-lucide="save" class="w-4 h-4"></i>
      </span>
      <span wire:loading wire:target="guardar">
        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
      </span>
      Guardar cambios
    </button>
  </div>

  @if($guardado)
    <div class="flash-msg mt-4 mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded-[11px]">
      <p class="font-bold text-sm">¡Éxito!</p>
      <p class="text-sm">La matriz de permisos se ha actualizado correctamente en la base de datos.</p>
    </div>
  @endif

  </div>

@script
<script>
  // Re-inicializar iconos Lucide después de cada actualización de Livewire
  lucide.createIcons();

  // Auto-ocultar mensajes flash después de 3 segundos
  document.querySelectorAll('.flash-msg').forEach(function(el) {
    setTimeout(function() {
      el.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
      el.style.opacity = '0';
      el.style.transform = 'translateY(-6px)';
      setTimeout(function() { el.remove(); }, 400);
    }, 3000);
  });
</script>
@endscript
