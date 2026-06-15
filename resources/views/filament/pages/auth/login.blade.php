<x-filament-panels::page.simple class="coal-login-page">
    <style>
        /* Full Screen Background & Layout */
        .fi-simple-layout {
            background: 
                linear-gradient(rgba(10, 10, 12, 0.70), rgba(5, 5, 6, 0.85)),
                url('/images/bg-batubara.jpg') center center / cover no-repeat !important;
            min-height: 100vh !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .fi-simple-main-ctn {
            width: 100%;
            padding: 1.5rem;
            display: flex;
            justify-content: center;
        }

        /* UBAH DI SINI: max-width dinaikkan agar kotak menjadi persegi panjang lebar */
        .fi-simple-main {
            width: 100% !important;
            max-width: 38rem !important; /* Diubah dari 26rem ke 38rem */
            overflow: hidden;
            padding: 3.5rem 4rem !important; /* Padding kanan-kiri diperlebar agar seimbang */
            border-radius: 1.5rem !important;
            background: rgba(18, 18, 22, 0.45) !important;
            backdrop-filter: blur(24px) saturate(120%);
            -webkit-backdrop-filter: blur(24px) saturate(120%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 
                0 4px 30px rgba(0, 0, 0, 0.4),
                0 2rem 4rem rgba(0, 0, 0, 0.6) !important;
        }

        /* Hide Default Header Elements */
        .coal-login-page .fi-simple-header {
            display: none;
        }

        /* Inner Layout Alignment */
        .coal-login-shell {
            background: transparent !important;
            display: block !important;
        }

        /* Center Badge & Identity */
        .coal-login-brand {
            display: flex;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .coal-login-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: 99px;
            background: rgba(245, 158, 11, 0.08);
            padding: 0.45rem 1rem;
            color: #fbbf24;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        /* Form Header Typography */
        .coal-login-form-heading {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .coal-login-form-heading h2 {
            margin: 0;
            color: #ffffff;
            font-size: 1.85rem; /* Sedikit diperbesar agar seimbang dengan kotak yang lebar */
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: -0.01em;
        }

        .coal-login-form-heading span {
            display: block;
            margin-top: 0.6rem;
            color: #94a3b8;
            font-size: 0.925rem;
            line-height: 1.5;
        }

        /* Input & Button Customizations */
        .fi-simple-main .fi-btn {
            border-radius: 0.625rem !important;
            background: #f59e0b !important;
            color: #000000 !important;
            font-weight: 700 !important;
            margin-top: 0.75rem;
            padding-top: 0.625rem !important;
            padding-bottom: 0.625rem !important;
            transition: all 0.2s ease;
        }

        .fi-simple-main .fi-btn:hover {
            background: #d97706 !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(217, 119, 6, 0.2);
        }

        .fi-simple-main .fi-input-wrp {
            border-radius: 0.625rem !important;
            background: rgba(0, 0, 0, 0.2) !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
            backdrop-filter: blur(4px);
        }
        
        .fi-simple-main .fi-input-wrp:focus-within {
            border-color: #f59e0b !important;
            box-shadow: 0 0 0 1px #f59e0b !important;
        }

        /* Filament labels & text adjustments for dark aesthetic */
        .fi-simple-main label {
            color: #cbd5e1 !important;
            font-weight: 500 !important;
        }
    </style>
    <div class="coal-login-shell">
        <div class="coal-login-brand">
            <div class="coal-login-badge">
                <span></span>
                Login Page
            </div>
        </div>

        <div class="coal-login-form-heading">
            <h2>Coal Movement Dashboard</h2>
            <span>Gunakan Username dan Password Anda untuk mengakses dashboard.</span>
        </div>

        @if (filament()->hasRegistration())
            <div class="mb-6 text-sm text-center text-gray-400">
                {{ __('filament-panels::pages/auth/login.actions.register.before') }}
                {{ $this->registerAction }}
            </div>
        @endif

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}

        <x-filament-panels::form id="form" wire:submit="authenticate">
            {{ $this->form }}

            <x-filament-panels::form.actions
                :actions="$this->getCachedFormActions()"
                :full-width="$this->hasFullWidthFormActions()"
            />
        </x-filament-panels::form>

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}

        @filamentScripts
    </div>
</x-filament-panels::page.simple>