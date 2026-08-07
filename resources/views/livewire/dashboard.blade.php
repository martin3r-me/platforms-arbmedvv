{{-- ArbMedVV Dashboard – Katalog-Übersicht (nx-Design) --}}
@php
    $careTypeVariant = ['mandatory' => 'danger', 'offered' => 'info', 'request' => 'success', 'follow_up' => 'neutral'];
@endphp

<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="ArbMedVV" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'ArbMedVV', 'icon' => 'clipboard-document-check'],
        ]">
            <x-nx-button variant="primary" size="sm" :href="route('arbmedvv.occasions.index')" wire:navigate>
                @svg('heroicon-o-list-bullet', 'w-4 h-4')
                <span>Zum Katalog</span>
            </x-nx-button>
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container width="contained" spacing="space-y-6">

        <x-nx-stat-grid :cols="4">
            <x-nx-stat label="Anlässe gesamt" :value="$stats['total']" icon="heroicon-o-clipboard-document-check" />
            <x-nx-stat label="Pflichtvorsorge" :value="$stats['mandatory']" icon="heroicon-o-exclamation-triangle" accent="var(--nx-danger)" />
            <x-nx-stat label="Angebotsvorsorge" :value="$stats['offered']" icon="heroicon-o-hand-raised" accent="var(--nx-info)" />
            <x-nx-stat label="Nachgehend" :value="$stats['follow_up']" icon="heroicon-o-arrow-uturn-right" />
        </x-nx-stat-grid>

        @if($stats['total'] === 0)
            <x-nx-card>
                <x-nx-empty icon="heroicon-o-clipboard-document-check">
                    Noch keine Vorsorge-Anlässe im Katalog.
                    Lege Anlässe aus dem Anhang der ArbMedVV an – manuell oder per LLM-Tool.
                    <x-slot name="action">
                        <x-nx-button variant="primary" size="sm" :href="route('arbmedvv.occasions.index')" wire:navigate>
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
                                :href="route('arbmedvv.occasions.index', ['filterSection' => $group['key']])" wire:navigate>
                                Alle
                            </x-nx-button>
                        </x-slot>

                        <x-nx-card flush class="divide-y divide-[color:var(--nx-line)]">
                            @foreach($group['occasions'] as $occasion)
                                <x-nx-list-item
                                    :href="route('arbmedvv.occasions.show', $occasion)"
                                    wire:navigate
                                    icon="heroicon-o-document-text"
                                    :title="$occasion->title"
                                    :subtitle="$occasion->threshold">
                                    <x-slot name="trailing">
                                        <x-nx-badge :variant="$careTypeVariant[$occasion->care_type] ?? 'neutral'" dot>
                                            {{ $occasion->careTypeLabel() }}
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

    {{-- Linke Sidebar: Übersicht --}}
    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[color:var(--nx-faint)] mb-3">Nach Vorsorgeart</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-2"><x-nx-badge variant="danger" dot>Pflicht</x-nx-badge></span>
                            <span class="text-[color:var(--nx-muted)]">{{ $stats['mandatory'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-2"><x-nx-badge variant="info" dot>Angebot</x-nx-badge></span>
                            <span class="text-[color:var(--nx-muted)]">{{ $stats['offered'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-2"><x-nx-badge variant="neutral" dot>Nachgehend</x-nx-badge></span>
                            <span class="text-[color:var(--nx-muted)]">{{ $stats['follow_up'] }}</span>
                        </div>
                    </div>
                </div>
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[color:var(--nx-faint)] mb-3">Nach Teil</h3>
                    <div class="space-y-2 text-sm">
                        @foreach($grouped as $group)
                            <a href="{{ route('arbmedvv.occasions.index', ['filterSection' => $group['key']]) }}" wire:navigate
                               class="flex items-center justify-between rounded-md -mx-2 px-2 py-1 hover:bg-[var(--nx-hover)]">
                                <span class="text-[color:var(--nx-text)] truncate">{{ config('arbmedvv.sections_short.'.$group['key'], $group['label']) }}</span>
                                <span class="text-[color:var(--nx-muted)] ml-2">{{ $group['count'] }}</span>
                            </a>
                        @endforeach
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
