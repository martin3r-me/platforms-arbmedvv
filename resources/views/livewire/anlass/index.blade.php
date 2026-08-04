{{-- ArbMedVV – Anlass-Katalog (Liste nach Teil gruppiert, Suche + Filter + Anlege-Modal) --}}
@php
    $artVariant = ['pflicht' => 'danger', 'angebot' => 'info', 'nachgehend' => 'neutral'];
    $teilOptions = collect($teile)->map(fn ($label, $key) => ['value' => $key, 'label' => $label])->values()->all();
    $artOptions  = collect($vorsorgearten)->map(fn ($label, $key) => ['value' => $key, 'label' => $label])->values()->all();
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
                <x-nx-input-select name="filterTeil" label="Teil" wire:model.live="filterTeil"
                    nullable nullLabel="Alle Teile" :options="$teilOptions" />
                <x-nx-input-select name="filterVorsorgeart" label="Vorsorgeart" wire:model.live="filterVorsorgeart"
                    nullable nullLabel="Alle Arten" :options="$artOptions" />
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
                <x-nx-section icon="heroicon-o-folder" :title="$group['label']" :hint="$group['anlaesse']->count()">
                    <x-nx-card flush class="divide-y divide-[color:var(--nx-line)]">
                        @foreach($group['anlaesse'] as $anlass)
                            <x-nx-list-item
                                :href="route('arbmedvv.anlaesse.show', $anlass)"
                                wire:navigate
                                icon="heroicon-o-document-text"
                                :title="$anlass->titel"
                                :subtitle="$anlass->grenzwert"
                                :meta="$anlass->rechtsgrundlage">
                                <x-slot name="trailing">
                                    <x-nx-badge :variant="$artVariant[$anlass->vorsorgeart] ?? 'neutral'" dot>
                                        {{ $anlass->vorsorgeartLabel() }}
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
                <x-nx-input-select name="form.teil" label="Teil" wire:model="form.teil" :options="$teilOptions" required />
                <x-nx-input-select name="form.vorsorgeart" label="Vorsorgeart" wire:model="form.vorsorgeart" :options="$artOptions" required />
            </div>
            <x-nx-input-text name="form.titel" label="Titel" wire:model="form.titel" placeholder="z.B. Feuchtarbeit" required />
            <x-nx-input-textarea name="form.ausloeser" label="Auslöser (Gefährdungs-/Expositionstatbestand)"
                wire:model="form.ausloeser" :rows="3" placeholder="Wortlaut des auslösenden Tatbestands" required />
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-nx-input-text name="form.grenzwert" label="Grenzwert / Schwelle" wire:model="form.grenzwert"
                    placeholder="z.B. ≥ 4 Std./Tag, 85 dB(A)" />
                <x-nx-input-text name="form.rechtsgrundlage" label="Rechtsgrundlage" wire:model="form.rechtsgrundlage"
                    placeholder="z.B. Anhang Teil 1 (2)" />
            </div>
            <x-nx-input-textarea name="form.beschreibung" label="Beschreibung / Hinweise" wire:model="form.beschreibung" :rows="3" />
        </div>

        <x-slot name="footer">
            <div class="flex justify-end gap-3">
                <x-nx-button variant="ghost" wire:click="$set('showCreate', false)">Abbrechen</x-nx-button>
                <x-nx-button variant="primary" wire:click="save">Speichern</x-nx-button>
            </div>
        </x-slot>
    </x-nx-modal>
</x-ui-page>
