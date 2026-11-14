<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\V1\AbsensiController;
use App\Http\Controllers\Api\V1\AdditionalItemController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DeliveryCheckController;
use App\Http\Controllers\Api\V1\FormOrderController;
use App\Http\Controllers\Api\V1\FormPenawaranController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\JadwalOperasionalController;
use App\Http\Controllers\Api\V1\LaporanController;
use App\Http\Controllers\Api\V1\MasterArmadaController;
use App\Http\Controllers\Api\V1\MasterBarangController;
use App\Http\Controllers\Api\V1\MasterKaryawanController;
use App\Http\Controllers\Api\V1\MasterKlienController;
use App\Http\Controllers\Api\V1\MasterPaketController;
use App\Http\Controllers\Api\V1\MasterPerusahaanController;
use App\Http\Controllers\Api\V1\SuratJalanController;
use App\Http\Controllers\Api\V1\TransaksiKeuanganController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/v1/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::get('/login', fn () => abort(401))->name('login');
Route::middleware('auth:sanctum')->prefix('v1')->group(function (): void {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/dashboard', DashboardController::class);
});

Route::apiResource('v1/master-barang', MasterBarangController::class)->middleware('auth:sanctum');

Route::apiResource('v1/master-paket', MasterPaketController::class)->middleware('auth:sanctum');

Route::apiResource('v1/master-karyawan', MasterKaryawanController::class)->middleware('auth:sanctum');

Route::apiResource('v1/master-klien', MasterKlienController::class)->middleware('auth:sanctum');

Route::apiResource('v1/master-armada', MasterArmadaController::class)->middleware('auth:sanctum');

Route::apiResource('v1/form-order', FormOrderController::class)->middleware('auth:sanctum');

Route::apiResource('v1/form-penawaran', FormPenawaranController::class)->middleware('auth:sanctum');
Route::apiResource('v1/surat-jalan', SuratJalanController::class)->except(['show'])->middleware('auth:sanctum');
Route::apiResource('v1/jadwal-operasional', JadwalOperasionalController::class)->except(['show'])->middleware('auth:sanctum');
Route::apiResource('v1/invoice', InvoiceController::class)->except(['show'])->middleware('auth:sanctum');
Route::apiResource('v1/transaksi-keuangan', TransaksiKeuanganController::class)->except(['show'])->middleware('auth:sanctum');
Route::get('v1/laporan/summary', [LaporanController::class, 'summary'])->middleware('auth:sanctum');
Route::get('v1/laporan/transactions.csv', [LaporanController::class, 'transactionsCsv'])->middleware('auth:sanctum');

// Owner only
Route::middleware(['auth:sanctum', 'check.role:owner'])->prefix('v1')->group(function (): void {
    Route::apiResource('master-perusahaan', MasterPerusahaanController::class);
    Route::get('users', [UserController::class, 'index']);
    Route::get('roles', [UserController::class, 'roles']);
    Route::post('users', [UserController::class, 'store']);
    Route::put('users/{user}', [UserController::class, 'update']);
    Route::delete('users/{user}', [UserController::class, 'destroy']);
});

// Admin only
Route::middleware(['auth:sanctum', 'check.role:admin,owner'])->prefix('v1')->group(function (): void {
    Route::apiResource('absensi', AbsensiController::class);
    Route::get('surat-jalan/teknisi-options', [SuratJalanController::class, 'teknisiOptions']);
    Route::apiResource('surat-jalan', SuratJalanController::class)->except(['show']);
    Route::apiResource('jadwal-operasional', JadwalOperasionalController::class)->except(['show']);
});

// Teknisi (Mobile)
Route::middleware(['auth:sanctum', 'check.role:teknisi,admin,owner'])->prefix('v1')->group(function (): void {
    Route::post('delivery/scan-surat-jalan', [DeliveryCheckController::class, 'scanSuratJalan']);
    Route::get('surat-jalan/{suratJalan}/photos', [DeliveryCheckController::class, 'photos']);
    Route::post('delivery/checkout', [DeliveryCheckController::class, 'checkout']);
    Route::post('delivery/checkin', [DeliveryCheckController::class, 'checkin']);
    Route::post('delivery-additional-items', [AdditionalItemController::class, 'store']);
    Route::get('delivery-additional-items/{suratJalanId}', [AdditionalItemController::class, 'indexBySuratJalan']);
});
