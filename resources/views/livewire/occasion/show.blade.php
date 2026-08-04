{{-- ArbMedVV – Anlass-Detail + Inline-Edit (nx-Design) --}}
@php
    $careTypeVariant = ['mandatory' => 'danger', 'offered' => 'info', 'follow_up' => 'neutral'];
    $sectionOptions = collect($sections)->map(fn ($label, $key) => ['value' => $key, 'label' => $label])->values()->all();
    $careTypeOptions = collect($careTypes)->map(fn ($label, $key) => ['value' => $key, 'label' => $label])->values()->all();
    $statusOptions = [
        ['value' => 'active', 'label' => 'Aktiv'],
        ['value' => 'archived', 'label' => 'Archiviert'],
    ];
@endphp

<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$occasion->title" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'ArbMedVV', 'icon' => 'clipboard-document-check', 'route' => 'arbmedvv.dashboard'],
            ['label' => 'Anlässe', 'route' => 'arbmedvv.occasions.index'],
            ['label' => $occasion->title],
        ]">
            @unless($editing)
                <x-nx-dropdown label="Aktionen">
                    <x-nx-dropdown-item wire:click="edit">Bearbeiten</x-nx-dropdown-item>
                    <x-nx-dropdown-divider />
                    <x-nx-dropdown-item variant="danger"
                        wire:click="delete"
                        wire:confirm="Diesen Anlass wirklich löschen?">Löschen</x-nx-dropdown-item>
                </x-nx-dropdown>
            @endunless
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container width="contained" spacing="space-y-6">

        @if($editing)
            {{-- ── Bearbeiten ── --}}
            <x-nx-card>
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <x-nx-input-select name="form.section" label="Teil" wire:model="form.section" :options="$sectionOptions" required />
                        <x-nx-input-select name="form.care_type" label="Vorsorgeart" wire:model="form.care_type" :options="$careTypeOptions" required />
                        <x-nx-input-select name="form.status" label="Status" wire:model="form.status" :options="$statusOptions" required />
                    </div>
                    <x-nx-input-text name="form.title" label="Titel" wire:model="form.title" required />
                    <x-nx-input-textarea name="form.trigger" label="Auslöser (Gefährdungs-/Expositionstatbestand)" wire:model="form.trigger" :rows="3" required />
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-nx-input-text name="form.threshold" label="Grenzwert / Schwelle" wire:model="form.threshold" />
                        <x-nx-input-text name="form.legal_basis" label="Rechtsgrundlage" wire:model="form.legal_basis" />
                    </div>
                    <x-nx-input-textarea name="form.description" label="Beschreibung / Hinweise" wire:model="form.description" :rows="3" />

                    <div class="flex justify-end gap-3 pt-2">
                        <x-nx-button variant="ghost" wire:click="$set('editing', false)">Abbrechen</x-nx-button>
                        <x-nx-button variant="primary" wire:click="save">Speichern</x-nx-button>
                    </div>
                </div>
            </x-nx-card>
        @else
            {{-- ── Ansicht ── --}}
            <div class="flex flex-wrap items-center gap-2">
                <x-nx-badge variant="neutral">{{ $occasion->sectionLabel() }}</x-nx-badge>
                <x-nx-badge :variant="$careTypeVariant[$occasion->care_type] ?? 'neutral'" dot>{{ $occasion->careTypeLabel() }}</x-nx-badge>
                @if($occasion->status === 'archived')
                    <x-nx-badge variant="warning">Archiviert</x-nx-badge>
                @endif
            </div>

            <x-nx-section icon="heroicon-o-exclamation-triangle" title="Auslöser">
                <x-nx-card>
                    <p class="text-sm text-[color:var(--nx-text)] whitespace-pre-line">{{ $occasion->trigger }}</p>
                </x-nx-card>
            </x-nx-section>

            <x-nx-card flush class="divide-y divide-[color:var(--nx-line)]">
                <x-nx-property-row icon="heroicon-o-scale" label="Grenzwert / Schwelle">
                    {{ $occasion->threshold ?: '—' }}
                </x-nx-property-row>
                <x-nx-property-row icon="heroicon-o-book-open" label="Rechtsgrundlage">
                    {{ $occasion->legal_basis ?: '—' }}
                </x-nx-property-row>
            </x-nx-card>

            @if($occasion->description)
                <x-nx-section icon="heroicon-o-document-text" title="Beschreibung / Hinweise">
                    <x-nx-card>
                        <p class="text-sm text-[color:var(--nx-muted)] whitespace-pre-line">{{ $occasion->description }}</p>
                    </x-nx-card>
                </x-nx-section>
            @endif
        @endif

    </x-ui-page-container>

    {{-- Linke Sidebar: Eigenschaften --}}
    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[color:var(--nx-faint)] mb-3">Eigenschaften</h3>
                    <div class="space-y-3 text-sm">
                        <div>
                            <div class="text-xs text-[color:var(--nx-faint)]">Teil</div>
                            <div class="text-[color:var(--nx-text)]">{{ $occasion->sectionShortLabel() }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-[color:var(--nx-faint)]">Vorsorgeart</div>
                            <div><x-nx-badge :variant="$careTypeVariant[$occasion->care_type] ?? 'neutral'" dot>{{ $occasion->careTypeLabel() }}</x-nx-badge></div>
                        </div>
                        <div>
                            <div class="text-xs text-[color:var(--nx-faint)]">Status</div>
                            <div class="text-[color:var(--nx-text)]">{{ $occasion->status === 'active' ? 'Aktiv' : 'Archiviert' }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-[color:var(--nx-faint)]">Rechtsgrundlage</div>
                            <div class="text-[color:var(--nx-text)]">{{ $occasion->legal_basis ?: '—' }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-[color:var(--nx-faint)]">Grenzwert</div>
                            <div class="text-[color:var(--nx-text)]">{{ $occasion->threshold ?: '—' }}</div>
                        </div>
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
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[color:var(--nx-faint)] mb-3">Verlauf</h3>
                    <div class="space-y-3 text-sm">
                        <div>
                            <div class="text-xs text-[color:var(--nx-faint)]">Erstellt</div>
                            <div class="text-[color:var(--nx-text)]">{{ $occasion->created_at?->format('d.m.Y H:i') }}</div>
                            @if($occasion->createdByUser)
                                <div class="text-xs text-[color:var(--nx-muted)]">von {{ $occasion->createdByUser->name }}</div>
                            @endif
                        </div>
                        <div>
                            <div class="text-xs text-[color:var(--nx-faint)]">Zuletzt geändert</div>
                            <div class="text-[color:var(--nx-text)]">{{ $occasion->updated_at?->format('d.m.Y H:i') }}</div>
                            <div class="text-xs text-[color:var(--nx-muted)]">{{ $occasion->updated_at?->diffForHumans() }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
