<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/playground', [App\Http\Controllers\PlaygroundController::class, 'index'])->name('playground');

Route::get('/finance/payroll',          [App\Http\Controllers\Finance\PayrollController::class,   'index'])->name('finance.payroll');
Route::post('/finance/payroll/getdata', [App\Http\Controllers\Finance\PayrollController::class, 'getData'])->name('finance.payroll.getdata');

// Make sure these are within your authenticated middleware group
Route::get('/time/timesheet',         [App\Http\Controllers\Time\TimesheetController::class,         'index'])->name('time.timesheet');
Route::post('/time/timesheet/data',   [App\Http\Controllers\Time\TimesheetController::class,       'getData']);
Route::post('/time/timesheet/recent', [App\Http\Controllers\Time\TimesheetController::class, 'getRecentRows']);
Route::post('/time/timesheet/save',   [App\Http\Controllers\Time\TimesheetController::class, 'saveTimesheet']);

// ================================================================
//  Employee Time — Historian
// ================================================================
Route::get('/time/employee-time/historian', [App\Http\Controllers\Time\EmployeeTimeHistorianController::class, 'index'])
    ->name('time.employee-time-historian.index');

Route::post('/time/employee-time/historian/get-data', [App\Http\Controllers\Time\EmployeeTimeHistorianController::class, 'getData'])
    ->name('time.employee-time-historian.getData');

// ================================================================
//  Employee Time — Aggregated
// ================================================================
Route::get('/time/employee-time/summary', [App\Http\Controllers\Time\EmployeeTimeSummaryController::class, 'index'])
    ->name('time.employee-time-summary.index');

Route::post('/time/employee-time/summary/get-data', [App\Http\Controllers\Time\EmployeeTimeSummaryController::class, 'getData'])
    ->name('time.employee-time-summary.getData');

Route::get('/ping', function () {
    return 'pong';
});