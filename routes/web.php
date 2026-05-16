<?php

use App\Livewire\ProductSearch;
use Illuminate\Support\Facades\Route;

Route::get('/', ProductSearch::class)->name('search');
