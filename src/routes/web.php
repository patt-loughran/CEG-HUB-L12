<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/playground', [App\Http\Controllers\PlaygroundController::class, 'index'])->name('playground');

Route::get('/finance/payroll', [App\Http\Controllers\Finance\PayrollController::class, 'index'])->name('finance.payroll');
Route::post('/finance/payroll/getdata', [App\Http\Controllers\Finance\PayrollController::class, 'getData'])->name('finance.payroll.getdata');

// Add this new route
Route::get('/ping', function () {
    return 'pong';
});