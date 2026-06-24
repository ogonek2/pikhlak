<?php

use App\Http\Controllers\ClientPaymentController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::get('pay/{payment}', [ClientPaymentController::class, 'show'])->name('pay.show');

Route::prefix('admin')->name('admin.')->group(base_path('routes/admin.php'));
