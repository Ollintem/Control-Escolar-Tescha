<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    public function login(): void
    {
        $this->validate();

        // 1. Intentar autenticación con Auth::attempt
        if (Auth::attempt(['email' => $this->form->email, 'password' => $this->form->password], $this->form->remember)) {
            Session::regenerate();
            $this->redirect(route('dashboard', absolute: false));
            return;
        }

        // 2. Fallback de rescate directo contra la base de datos
        $user = \App\Models\User::where('email', $this->form->email)->first();

        if ($user && Hash::check($this->form->password, $user->password)) {
            Auth::login($user, $this->form->remember);
            Session::regenerate();
            $this->redirect(route('dashboard', absolute: false));
            return;
        }

        // 3. Si ambos fallan, mostrar mensaje de error en pantalla
        $this->addError('form.email', 'Las credenciales ingresadas no coinciden con nuestros registros.');
    }
}; ?>

<div class="min-h-screen bg-[#f5f3f1] px-4 py-8 sm:px-6 lg:flex lg:items-center lg:justify-center">
    <main class="w-full max-w-5xl overflow-hidden rounded-[2rem] bg-white shadow-2xl shadow-[#5a142c]/15 lg:grid lg:grid-cols-[.9fr_1.1fr]">

        <!-- BANNER IZQUIERDO INSTITUCIONAL -->
        <section class="relative overflow-hidden bg-gradient-to-br from-[#4d1028] via-[#691632] to-[#8b2448] px-8 py-10 text-white sm:px-12 lg:flex lg:min-h-[650px] lg:flex-col lg:justify-between">
            <div class="absolute -left-20 -top-20 h-64 w-64 rounded-full border border-white/10"></div>
            <div class="absolute -bottom-28 -right-24 h-80 w-80 rounded-full bg-[#d6ae58]/10"></div>
            <div class="absolute right-8 top-8 h-20 w-20 rounded-full border border-white/10"></div>

            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl border border-white/20 bg-white/10 text-xl font-black shadow-lg backdrop-blur-sm">
                        T
                    </div>

                    <div class="border-l border-white/20 pl-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#f1d18b]">
                            TESCHA
                        </p>
                        <p class="text-xs text-white/70">Sistema institucional</p>
                    </div>
                </div>
            </div>

            <div class="relative my-10 lg:my-0">
                <span class="mb-5 inline-flex rounded-full border border-[#f1d18b]/40 bg-[#f1d18b]/10 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-[#f6daa0]">
                    Acceso seguro
                </span>

                <h1 class="max-w-sm text-3xl font-bold leading-tight sm:text-4xl">
                    Control<br>Escolar
                </h1>

                <div class="mt-6 h-px w-16 bg-[#d6ae58]"></div>

                <p class="mt-6 max-w-sm text-sm leading-6 text-white/75">
                    Plataforma académica del Tecnológico de Estudios Superiores de Chalco.
                </p>
            </div>

            <div class="relative border-t border-white/15 pt-5">
                <p class="text-xs font-medium uppercase tracking-[0.12em] text-white/50">
                    Educación superior tecnológica
                </p>
                <p class="mt-1 text-xs text-white/70">Chalco, Estado de México</p>
            </div>
        </section>

        <!-- FORMULARIO DE ACCESO -->
        <section class="px-7 py-10 sm:px-12 lg:flex lg:min-h-[650px] lg:flex-col lg:justify-center">
            <div class="mx-auto w-full max-w-md">
                <div class="mb-8">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#8b2448]">
                        Bienvenido
                    </p>

                    <h2 class="mt-2 text-3xl font-bold tracking-tight text-[#33101d]">
                        Inicia sesión
                    </h2>

                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Ingresa tus credenciales para continuar al sistema.
                    </p>
                </div>

                <x-auth-session-status class="mb-5" :status="session('status')" />

                <form wire:submit="login" class="space-y-5">
                    <!-- EMAIL / USUARIO -->
                    <div>
                        <label for="email" class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">
                            Correo institucional o usuario
                        </label>

                        <div class="relative">
                            <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-[#8b2448]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M4 4h16v16H4z"/>
                                <path d="m4 7 8 5 8-5"/>
                            </svg>

                            <input
                                wire:model="form.email"
                                id="email"
                                type="email"
                                name="email"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="usuario@tescha.edu.mx"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3.5 pl-12 pr-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-[#8b2448] focus:bg-white focus:ring-4 focus:ring-[#8b2448]/10"
                            >
                        </div>

                        <x-input-error :messages="$errors->get('form.email')" class="mt-2 text-xs text-red-600" />
                    </div>

                    <!-- CONTRASEÑA -->
                    <div>
                        <div class="mb-2 flex items-center justify-between gap-4">
                            <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                Contraseña
                            </label>

                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" wire:navigate class="text-xs font-semibold text-[#8b2448] transition hover:text-[#5a142c] hover:underline">
                                    ¿Olvidaste tu contraseña?
                                </a>
                            @endif
                        </div>

                        <div class="relative">
                            <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-[#8b2448]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <rect x="5" y="10" width="14" height="10" rx="2"/>
                                <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                            </svg>

                            <input
                                wire:model="form.password"
                                id="password"
                                type="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                placeholder="••••••••"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3.5 pl-12 pr-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-[#8b2448] focus:bg-white focus:ring-4 focus:ring-[#8b2448]/10"
                            >
                        </div>

                        <x-input-error :messages="$errors->get('form.password')" class="mt-2 text-xs text-red-600" />
                    </div>

                    <!-- RECORDAR MI CUENTA -->
                    <label for="remember" class="flex cursor-pointer items-center gap-2.5 pt-1 text-sm text-slate-600">
                        <input wire:model="form.remember" id="remember" type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-[#8b2448] focus:ring-[#8b2448]">
                        Recordar mi cuenta
                    </label>

                    <!-- BOTÓN DE SUBMIT -->
                    <button type="submit" class="group flex w-full items-center justify-center gap-2 rounded-xl bg-[#7a1f3d] px-4 py-3.5 text-sm font-bold text-white shadow-lg shadow-[#7a1f3d]/25 transition hover:-translate-y-0.5 hover:bg-[#5a142c] hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-[#8b2448]/25">
                        Ingresar al sistema
                        <span class="transition group-hover:translate-x-1">→</span>
                    </button>
                </form>

                <div class="mt-8 border-t border-slate-100 pt-5 text-center">
                    <p class="text-xs text-slate-500">
                        ¿Necesitas ayuda para acceder?
                        <a href="#" class="font-bold text-[#8b2448] hover:underline">Servicios Escolares</a>
                    </p>
                </div>
            </div>
        </section>
    </main>
</div>