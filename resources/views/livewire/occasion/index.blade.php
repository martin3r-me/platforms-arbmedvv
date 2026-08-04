{{-- ArbMedVV – Anlass-Katalog (Liste nach Teil gruppiert, Suche + Filter + Anlege-Modal) --}}
@php
    $careTypeVariant = ['mandatory' => 'danger', 'offered' => 'info', 'follow_up' => 'neutral'];
    $sectionOptions = collect($sections)->map(fn ($label, $key) => ['value' => $key, 'label' => $label])->values()->all();
    $careTypeOptions = collect($careTypes)->map(fn ($label, $key) => ['value' => $key, 'label' => $label])->values()->all();
@endphp

<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="ArbMedVV – Katalog" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'ArbMedVV', 'icon' => 'clipboard-document-check', 'route' => 'arbmedvv.dashboard'],
            ['label' => 'Anlässe'],
        ]">
            <x-nx-button variant="primary" size="sm" wire:click="openCreate">
                @svg('heroicon-o-plus', 'w-4 h-4')
                <span>Neuer Anlass</span>
            </x-nx-button>
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container width="contained" spacing="space-y-6">

        {{-- Such- und Filterleiste --}}
        <x-nx-card>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-nx-input-text name="search" label="Suche" wire:model.live.debounce.300ms="search"
                    placeholder="Titel, Auslöser, Grenzwert …" />
                <x-nx-input-select name="filterSection" label="Teil" wire:model.live="filterSection"
                    nullable nullLabel="Alle Teile" :options="$sectionOptions" />
                <x-nx-input-select name="filterCareType" label="Vorsorgeart" wire:model.live="filterCareType"
                    nullable nullLabel="Alle Arten" :options="$careTypeOptions" />
            </div>
        </x-nx-card>

        @if($total === 0)
            <x-nx-card>
                <x-nx-empty icon="heroicon-o-clipboard-document-check">
                    Keine Anlässe gefunden.
                    <x-slot name="action">
                        <x-nx-button variant="primary" size="sm" wire:click="openCreate">Anlass anlegen</x-nx-button>
                    </x-slot>
                </x-nx-empty>
            </x-nx-card>
        @else
            @foreach($grouped as $group)
                <x-nx-section icon="heroicon-o-folder" :title="$group['label']" :hint="$group['occasions']->count()">
                    <x-nx-card flush class="divide-y divide-[color:var(--nx-line)]">
                        @foreach($group['occasions'] as $occasion)
                            <x-nx-list-item
                                :href="route('arbmedvv.occasions.show', $occasion)"
                                wire:navigate
                                icon="heroicon-o-document-text"
                                :title="$occasion->title"
                                :subtitle="$occasion->threshold"
                                :meta="$occasion->legal_basis">
                                <x-slot name="trailing">
                                    <x-nx-badge :variant="$careTypeVariant[$occasion->care_type] ?? 'neutral'" dot>
                                        {{ $occasion->careTypeLabel() }}
                                    </x-nx-badge>
                                </x-slot>
                            </x-nx-list-item>
                        @endforeach
                    </x-nx-card>
                </x-nx-section>
            @endforeach
        @endif

    </x-ui-page-container>

    {{-- Anlege-Modal (innerhalb x-ui-page) --}}
    <x-nx-modal size="lg" wire:model="showCreate">
        <x-slot name="header">Neuen Vorsorge-Anlass anlegen</x-slot>

        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-nx-input-select name="form.section" label="Teil" wire:model="form.section" :options="$sectionOptions" required />
                <x-nx-input-select name="form.care_type" label="Vorsorgeart" wire:model="form.care_type" :options="$careTypeOptions" required />
            </div>
            <x-nx-input-text name="form.title" label="Titel" wire:model="form.title" placeholder="z.B. Feuchtarbeit" required />
            <x-nx-input-textarea name="form.trigger" label="Auslöser (Gefährdungs-/Expositionstatbestand)"
                wire:model="form.trigger" :rows="3" placeholder="Wortlaut des auslösenden Tatbestands" required />
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-nx-input-text name="form.threshold" label="Grenzwert / Schwelle" wire:model="form.threshold"
                    placeholder="z.B. ≥ 4 Std./Tag, 85 dB(A)" />
                <x-nx-input-text name="form.legal_basis" label="Rechtsgrundlage" wire:model="form.legal_basis"
                    placeholder="z.B. Anhang Teil 1 (2)" />
            </div>
            <x-nx-input-textarea name="form.description" label="Beschreibung / Hinweise" wire:model="form.description" :rows="3" />
        </div>

        <x-slot name="footer">
            <div class="flex justify-end gap-3">
                <x-nx-button variant="ghost" wire:click="$set('showCreate', false)">Abbrechen</x-nx-button>
                <x-nx-button variant="primary" wire:click="save">Speichern</x-nx-button>
            </div>
        </x-slot>
    </x-nx-modal>

    {{-- Linke Sidebar: Übersicht --}}
    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[color:var(--nx-faint)] mb-3">Angezeigt</h3>
                    <div class="text-sm text-[color:var(--nx-muted)]">{{ $total }} {{ $total === 1 ? 'Anlass' : 'Anlässe' }}</div>
                </div>
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[color:var(--nx-faint)] mb-3">Legende Vorsorgeart</h3>
                    <div class="space-y-2">
                        <div><x-nx-badge variant="danger" dot>Pflichtvorsorge</x-nx-badge></div>
                        <div><x-nx-badge variant="info" dot>Angebotsvorsorge</x-nx-badge></div>
                        <div><x-nx-badge variant="neutral" dot>Nachgehende Vorsorge</x-nx-badge></div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    {{-- Rechte Sidebar: Aktivitäten --}}
    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-6 space-y-6">
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[color:var(--nx-faint)] mb-3">Zuletzt geändert</h3>
                    @forelse($recent as $occasion)
                        <a href="{{ route('arbmedvv.occasions.show', $occasion) }}" wire:navigate
                           class="block rounded-md -mx-2 px-2 py-2 hover:bg-[var(--nx-hover)]">
                            <div class="text-sm text-[color:var(--nx-text)] truncate">{{ $occasion->title }}</div>
                            <div class="text-xs text-[color:var(--nx-faint)]">{{ $occasion->updated_at?->diffForHumans() }}</div>
                        </a>
                    @empty
                        <div class="text-sm text-[color:var(--nx-muted)]">Keine Aktivitäten verfügbar.</div>
                    @endforelse
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
