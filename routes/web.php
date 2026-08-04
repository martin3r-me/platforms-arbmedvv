<?php

/**
 * ArbMedVV – web routes
 *
 * Prefix (from config): /arbmedvv
 */

use Platform\Arbmedvv\Livewire\Dashboard;
use Platform\Arbmedvv\Livewire\Occasion\Index as OccasionIndex;
use Platform\Arbmedvv\Livewire\Occasion\Show as OccasionShow;

// Dashboard – catalog overview
Route::get('/', Dashboard::class)->name('arbmedvv.dashboard');

// Occasion catalog
Route::get('/occasions', OccasionIndex::class)->name('arbmedvv.occasions.index');
Route::get('/occasions/{occasion}', OccasionShow::class)->name('arbmedvv.occasions.show');
