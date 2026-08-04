{{--
    Modul-Nav-Sidebar (nx-Design-System)
    Wird automatisch in die Haupt-Sidebar der Shell eingebunden (@livewire('arbmedvv.sidebar')).
    Shell-Komponenten x-ui-sidebar-list / x-ui-sidebar-item; Alpine-Scope `collapsed` von der Shell.
    Nur var(--nx-*) Tokens.
--}}

<div>
    {{-- Modul-Header --}}
    <div x-show="!collapsed" class="p-3 text-sm italic text-[var(--nx-text)] uppercase border-b border-[color:var(--nx-line)] mb-2">
        ArbMedVV
    </div>

    {{-- Abschnitt: Katalog --}}
    <x-ui-sidebar-list label="Katalog">
        <x-ui-sidebar-item :href="route('arbmedvv.dashboard')">
            @svg('heroicon-o-home', 'w-4 h-4 text-[var(--nx-text)]')
            <span class="ml-2 text-sm">Dashboard</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('arbmedvv.anlaesse.index')">
            @svg('heroicon-o-list-bullet', 'w-4 h-4 text-[var(--nx-text)]')
            <span class="ml-2 text-sm">Anlässe</span>
        </x-ui-sidebar-item>
    </x-ui-sidebar-list>

    {{-- Abschnitt: Teile (Schnellfilter mit Anzahl) --}}
    <x-ui-sidebar-list label="Teile">
        @foreach($teileKurz as $key => $label)
            <x-ui-sidebar-item :href="route('arbmedvv.anlaesse.index', ['filterTeil' => $key])">
                @svg('heroicon-o-folder', 'w-4 h-4 text-[var(--nx-text)]')
                <span class="ml-2 text-sm flex-1 truncate">{{ $label }}</span>
                @if(($teilCounts[$key] ?? 0) > 0)
                    <span class="text-xs text-[color:var(--nx-faint)]">{{ $teilCounts[$key] }}</span>
                @endif
            </x-ui-sidebar-item>
        @endforeach
    </x-ui-sidebar-list>

    {{-- Collapsed: Icons-only --}}
    <div x-show="collapsed" class="px-2 py-2 border-b border-[color:var(--nx-line)]">
        <div class="flex flex-col gap-2">
            <a href="{{ route('arbmedvv.dashboard') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--nx-text)] hover:bg-[var(--nx-bg)]">
                @svg('heroicon-o-home', 'w-5 h-5')
            </a>
            <a href="{{ route('arbmedvv.anlaesse.index') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--nx-text)] hover:bg-[var(--nx-bg)]">
                @svg('heroicon-o-list-bullet', 'w-5 h-5')
            </a>
        </div>
    </div>
</div>
