<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PnrController;

Route::post('/pnr/parse', [PnrController::class, 'parse']);
