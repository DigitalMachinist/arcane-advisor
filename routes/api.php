<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ConsultController;
use Illuminate\Support\Facades\Route;

Route::post('/consult', ConsultController::class);
