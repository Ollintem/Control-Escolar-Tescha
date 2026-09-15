<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: false);
    }
}; ?>

<div class="w-full max-w-md px-4 py-8">
    <!-- Tarjeta Principal Única -->
    <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden">
        
        <!-- Header Vino Institucional -->
        <div class="bg-[#5A142C] px-8 py-8 text-center relative overflow-hidden">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-[#7A1F3D] text-white font-extrabold text-2xl border border-white/20 shadow-md mb-3">
                T
            </div>
            <h1 class="text-2xl font-bold text-white tracking-wide">Control Escolar</h1>
            <p class="text-amber-200/90 text-xs font-semibold uppercase tracking-widest mt-1">Tecnológico de Estudios Superiores de Chalco</p>
        </div>

        <!-- Formulario -->
        <div class="p-8 space-y-5">
            <div class="text-center mb-2">
                <h2 class="text-xl font-bold text-[#5A142C]">¡Bienvenido de nuevo!</h2>
                <p class="text-xs text-gray-500 mt-0.5">Ingresa tus credenciales para acceder</p>
            </div>

            <!-- Estado de Sesión -->
            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form wire:submit="login" class="space-y-4">

                <!-- Usuario / Correo -->
                <div>
                    <label for="email" class="block text-xs font-bold text-[#333333] uppercase tracking-wider mb-1">
                        Correo Institucional / Usuario
                    </label>
                    <input 
                        wire:model="form.email" 
                        id="email" 
                        type="email" 
                        name="email" 
                        required 
                        autofocus 
                        autocomplete="username"
                        placeholder="usuario@tescha.edu.mx"
                        class="w-full px-4 py-2.5 text-sm bg-[#F3F3F3] border border-gray-300 rounded-lg text-[#333333] focus:outline-none focus:ring-2 focus:ring-[#7A1F3D] focus:bg-white transition"
                    >
                    <x-input-error :messages="$errors->get('form.email')" class="mt-1" />
                </div>

                <!-- Contraseña -->
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <label for="password" class="block text-xs font-bold text-[#333333] uppercase tracking-wider">
                            Contraseña
                        </label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" wire:navigate class="text-xs font-semibold text-[#7A1F3D] hover:underline">
                                ¿Olvidaste tu contraseña?
                            </a>
                        @endif
                    </div>
                    <input 
                        wire:model="form.password" 
                        id="password" 
                        type="password" 
                        name="password" 
                        required 
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="w-full px-4 py-2.5 text-sm bg-[#F3F3F3] border border-gray-300 rounded-lg text-[#333333] focus:outline-none focus:ring-2 focus:ring-[#7A1F3D] focus:bg-white transition"
                    >
                    <x-input-error :messages="$errors->get('form.password')" class="mt-1" />
                </div>

                <!-- Recordarme -->
                <div class="flex items-center justify-between pt-1">
                    <label for="remember" class="inline-flex items-center cursor-pointer">
                        <input wire:model="form.remember" id="remember" type="checkbox" name="remember" class="w-4 h-4 rounded text-[#7A1F3D] focus:ring-[#7A1F3D] border-gray-300">
                        <span class="ms-2 text-xs font-medium text-gray-600">Recordar mi cuenta</span>
                    </label>
                </div>

                <!-- Botón -->
                <button 
                    type="submit" 
                    class="w-full mt-2 bg-[#7A1F3D] hover:bg-[#5A142C] text-white font-bold py-3 px-4 rounded-lg shadow-md transition duration-200 text-sm"
                >
                    Ingresar al Sistema
                </button>
            </form>
        </div>

        <!-- Footer -->
        <div class="bg-[#F3F3F3] px-8 py-4 border-t border-gray-200 text-center">
            <p class="text-xs text-gray-500">
                ¿Dudas con tu acceso? <a href="#" class="font-bold text-[#7A1F3D] hover:underline">Servicios Escolares</a>
            </p>
        </div>

    </div>
</div>