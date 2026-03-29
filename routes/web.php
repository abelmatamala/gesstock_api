<?php
use Illuminate\Support\Facades\Route;

Route::view('/login', 'login-web')->name('login');
Route::redirect('/', '/login');

//Route::view('/login', 'login-web');
//Route::view('/seleccionar-sucursal', 'seleccionar-sucursal');
//Route::view('/panel-turnos', 'panel-turnos');

Route::get('/panel-turnos', function () {
    return view('panel-turnos');
});

Route::get('/seleccionar-sucursal', function () {
    return view('seleccionar-sucursal');
});