<?php

use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::controller(SiteController::class)->middleware('site.headers')->group(function () {
    Route::get('/', 'home')->name('home');
    Route::get('/menu', 'menu')->name('menu'); // the QR code points here
    Route::get('/about', 'about')->name('about');
    Route::get('/visit', 'visit')->name('visit');
});
