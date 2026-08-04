<?php

/**
 * ArbMedVV – Web-Routes
 *
 * Prefix (aus config): /arbmedvv
 */

use Platform\Arbmedvv\Livewire\Dashboard;
use Platform\Arbmedvv\Livewire\Anlass\Index as AnlassIndex;
use Platform\Arbmedvv\Livewire\Anlass\Show as AnlassShow;

// Dashboard – Katalog-Übersicht
Route::get('/', Dashboard::class)->name('arbmedvv.dashboard');

// Anlass-Katalog
Route::get('/anlaesse', AnlassIndex::class)->name('arbmedvv.anlaesse.index');
Route::get('/anlaesse/{anlass}', AnlassShow::class)->name('arbmedvv.anlaesse.show');
