{{-- ArbMedVV Dashboard – Katalog-Übersicht (nx-Design) --}}
@php
    $artVariant = ['pflicht' => 'danger', 'angebot' => 'info', 'nachgehend' => 'neutral'];
@endphp

<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="ArbMedVV" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'ArbMedVV', 'icon' => 'clipboard-document-check'],
        ]">
            <x-nx-button variant="primary" size="sm" :href="route('arbmedvv.anlaesse.index')" wire:navigate>
                @svg('heroicon-o-list-bullet', 'w-4 h-4')
                <span>Zum Katalog</span>
            </x-nx-button>
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container width="contained" spacing="space-y-6">

        <x-nx-stat-grid :cols="4">
            <x-nx-stat label="Anlässe gesamt" :value="$stats['total']" icon="heroicon-o-clipboard-document-check" />
            <x-nx-stat label="Pflichtvorsorge" :value="$stats['pflicht']" icon="heroicon-o-exclamation-triangle" accent="var(--nx-danger)" />
            <x-nx-stat label="Angebotsvorsorge" :value="$stats['angebot']" icon="heroicon-o-hand-raised" accent="var(--nx-info)" />
            <x-nx-stat label="Nachgehend" :value="$stats['nachgehend']" icon="heroicon-o-arrow-uturn-right" />
        </x-nx-stat-grid>

        @if($stats['total'] === 0)
            <x-nx-card>
                <x-nx-empty icon="heroicon-o-clipboard-document-check">
                    Noch keine Vorsorge-Anlässe im Katalog.
                    Lege Anlässe aus dem Anhang der ArbMedVV an – manuell oder per LLM-Tool.
                    <x-slot name="action">
                        <x-nx-button variant="primary" size="sm" :href="route('arbmedvv.anlaesse.index')" wire:navigate>
                            Ersten Anlass anlegen
                        </x-nx-button>
                    </x-slot>
                </x-nx-empty>
            </x-nx-card>
        @else
            @foreach($grouped as $group)
                @if($group['count'] > 0)
                    <x-nx-section icon="heroicon-o-folder" :title="$group['label']" :hint="$group['count']">
                        <x-slot name="action">
                            <x-nx-button variant="ghost" size="sm"
                                :href="route('arbmedvv.anlaesse.index', ['filterTeil' => $group['key']])" wire:navigate>
                                Alle
                            </x-nx-button>
                        </x-slot>

                        <x-nx-card flush class="divide-y divide-[color:var(--nx-line)]">
                            @foreach($group['anlaesse'] as $anlass)
                                <x-nx-list-item
                                    :href="route('arbmedvv.anlaesse.show', $anlass)"
                                    wire:navigate
                                    icon="heroicon-o-document-text"
                                    :title="$anlass->titel"
                                    :subtitle="$anlass->grenzwert">
                                    <x-slot name="trailing">
                                        <x-nx-badge :variant="$artVariant[$anlass->vorsorgeart] ?? 'neutral'" dot>
                                            {{ $anlass->vorsorgeartLabel() }}
                                        </x-nx-badge>
                                    </x-slot>
                                </x-nx-list-item>
                            @endforeach
                        </x-nx-card>
                    </x-nx-section>
                @endif
            @endforeach
        @endif

    </x-ui-page-container>
</x-ui-page>
