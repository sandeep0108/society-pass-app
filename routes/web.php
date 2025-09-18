<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\QrScanner;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/scanner', QrScanner::class)->middleware(['auth'])->name('scanner');

