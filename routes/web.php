<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});
Route::get('/habits', function () {
    return view('habits');
});
Route::get('/tasks', function () {
    return view('tasks');
});
Route::get('/ranking', function () {
    return view('ranking');
});
Route::get('/achievements', function () {
    return view('achievements');
});
Route::get('/store', function () {
    return view('store');
});
Route::get('/goals', function () {
    return view('goals');
});
Route::get('/history', function () {
    return view('history');
});

// Rotas protegidas e controllers serão adicionados depois
