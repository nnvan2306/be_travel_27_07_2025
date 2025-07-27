<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::get('/sanctum/csrf-cookie', function (Request $request) {
    return response()->noContent()->withCookie(
        Cookie::make('XSRF-TOKEN', csrf_token(), 120, '/', 'localhost', false, false)
    );
});