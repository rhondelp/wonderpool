<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

/*
| TEMPORARY (M0): component/theme preview, local environment only. Remove in M9.
*/
if (app()->environment('local')) {
    Route::get('/design-preview', function () {
        session()->now('success', 'Flash messages render here via x-ui.flash.');

        return view('design-preview');
    })->name('design-preview');
}
