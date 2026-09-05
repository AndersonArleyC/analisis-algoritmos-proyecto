<?php

use App\Http\Controllers\FlightSearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FlightSearchController::class, 'index'])->name('flights.index');
