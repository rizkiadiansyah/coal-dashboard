<x-filament-panels::page.simple class="coal-login-page">
    <style>
        form[wire\:submit="authenticate"] button[type="submit"]:disabled {
            cursor: wait !important;
            opacity: 0.8 !important;
        }

        form.coal-login-loading button[type="submit"],
        form.coal-login-submitting button[type="submit"] {
            position: relative;
            cursor: wait !important;
            pointer-events: none;
            color: transparent !important;
        }

        form.coal-login-loading button[type="submit"] > *,
        form.coal-login-submitting button[type="submit"] > * {
            visibility: hidden;
        }

        form.coal-login-loading button[type="submit"]::after,
        form.coal-login-submitting button[type="submit"]::after {
            content: '';
            position: absolute;
            left: 50%;
            top: 50%;
            width: 1rem;
            height: 1rem;
            margin: -0.5rem 0 0 -0.5rem;
            border: 2px solid rgba(28, 25, 23, 0.35);
            border-top-color: #1c1917;
            border-radius: 9999px;
            animation: coal-login-spin 0.7s linear infinite;
        }

        @keyframes coal-login-spin {
            to { transform: rotate(360deg); }
        }

        .fi-simple-layout {
            min-height: 100vh !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            background: 
                linear-gradient(rgba(15, 23, 42, 0.25), rgba(15, 23, 42, 0.45)),
                url('/images/bg-batubara.jpg') center bottom / cover no-repeat fixed !important;
        }

        html:not(.dark) .fi-simple-layout {
            background: 
                linear-gradient(rgba(255, 255, 255, 0.2), rgba(241, 245, 249, 0.4)),
                url('/images/bg-batubara.jpg') center bottom / cover no-repeat fixed !important;
        }

        .fi-simple-main-ctn {
            width: 100%;
            padding: 1.5rem;
            display: flex;
            justify-content: center;
        }

        .fi-simple-main {
            width: 100% !important;
            max-width: 32rem !important;
            padding: 2.5rem !important;
            border-radius: 0.875rem !important;
            background: rgba(15, 23, 42, 0.35) !important;
            -webkit-backdrop-filter: blur(6px) !important;
            border: 1px solid rgba(255, 255, 255, 0.18) !important;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3) !important;
            color: #ffffff !important;
        }

        html:not(.dark) .fi-simple-main {
            background: rgba(255, 255, 255, 0.5) !important;
            border: 1px solid rgba(255, 255, 255, 0.6) !important;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1) !important;
            color: #0f172a !important;
        }

        .coal-login-page .fi-simple-header {
            display: none;
        }

        .coal-login-shell {
            background: transparent !important;
            display: block !important;
        }

        .coal-login-form-heading {
            text-align: center !important;
            margin-bottom: 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
            padding-bottom: 1.25rem;
        }

        html:not(.dark) .coal-login-form-heading {
            border-bottom-color: rgba(15, 23, 42, 0.1);
        }

        .coal-login-form-heading h2 {
            margin: 0;
            color: #fbbf24 !important;
            font-size: 1.55rem;
            font-weight: 700;
            letter-spacing: -0.01em;
            text-transform: none !important;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.6);
        }

        html:not(.dark) .coal-login-form-heading h2 {
            color: #0f172a !important;
            text-shadow: none;
        }

        .coal-login-divider {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            margin-top: 0.75rem;
        }

        .coal-login-divider::before,
        .coal-login-divider::after {
            content: '';
            height: 1px;
            width: 2rem;
            background: rgba(251, 191, 36, 0.4);
        }

        .coal-login-divider span {
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: #e2e8f0 !important;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.6);
        }

        html:not(.dark) .coal-login-divider span {
            color: #475569 !important;
            text-shadow: none;
        }

        .fi-simple-main .fi-input-wrp {
            border-radius: 0.5rem !important;
            background: rgba(0, 0, 0, 0.35) !important;
            border: 1px solid rgba(255, 255, 255, 0.25) !important;
            backdrop-filter: blur(2px);
        }

        html:not(.dark) .fi-simple-main .fi-input-wrp {
            background: rgba(255, 255, 255, 0.75) !important;
            border: 1px solid rgba(15, 23, 42, 0.2) !important;
        }

        .fi-simple-main .fi-input-wrp:focus-within {
            border-color: #eab308 !important;
            box-shadow: 0 0 0 1px #eab308 !important;
        }

        .fi-simple-main .fi-input-wrp .fi-input {
            color: #ffffff !important;
        }

        html:not(.dark) .fi-simple-main .fi-input-wrp .fi-input {
            color: #0f172a !important;
        }

        .fi-simple-main label {
            color: #ffffff !important;
            font-weight: 600 !important;
            font-size: 0.875rem !important;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
        }

        html:not(.dark) .fi-simple-main label {
            color: #0f172a !important;
            text-shadow: none;
        }

        .fi-simple-main .fi-btn {
            border-radius: 0.5rem !important;
            background: #eab308 !important;
            color: #1c1917 !important;
            font-weight: 700 !important;
            letter-spacing: 0.025em;
            padding-top: 0.65rem !important;
            padding-bottom: 0.65rem !important;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.25);
            transition: all 0.15s ease;
        }

        .fi-simple-main .fi-btn:hover {
            background: #ca8a04 !important;
            transform: translateY(-1px);
        }
    </style>

    <script>
        document.addEventListener('submit', (event) => {
            const form = event.target.closest('form[wire\\:submit="authenticate"]');

            if (form) {
                form.classList.add('coal-login-submitting');
            }
        }, true);
    </script>

    <div class="coal-login-shell">
        <div class="coal-login-form-heading">
            <h2>Coal Movement Dashboard</h2>
            <div class="coal-login-divider">
                <span>Login Page</span>
            </div>
        </div>

        @if (filament()->hasRegistration())
            <div class="mb-6 text-sm text-center text-gray-200">
                {{ __('filament-panels::pages/auth/login.actions.register.before') }}
                {{ $this->registerAction }}
            </div>
        @endif

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}

        <x-filament-panels::form
            id="form"
            wire:submit="authenticate"
            x-data="{ isSubmitting: false }"
            x-on:submit="isSubmitting = true"
            x-bind:class="{ 'coal-login-loading': isSubmitting }"
            wire:loading.class="pointer-events-none opacity-80 coal-login-loading"
            wire:target="authenticate"
        >
            {{ $this->form }}

            <x-filament-panels::form.actions
                :actions="$this->getCachedFormActions()"
                :full-width="$this->hasFullWidthFormActions()"
            />
        </x-filament-panels::form>

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}
    </div>
</x-filament-panels::page.simple>