<?php

use App\Http\Controllers\IzinController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\LemburController;
use App\Http\Controllers\CutiController;
use App\Http\Controllers\JabatanController;
use App\Http\Controllers\DinasLuarKotaController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\TaskController;

// Rute Publik
Route::post('register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rute yang Memerlukan Autentikasi Menggunakan Sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::get('user', [AuthController::class, 'user']);
    Route::post('logout', [AuthController::class, 'logout']);

    // Rute untuk AbsensiController
    Route::get('/absensi', [AbsensiController::class, 'index']);
    Route::post('/absensi/created', [AbsensiController::class, 'store']);
    Route::get('/absensi/{id}', [AbsensiController::class, 'show']);
    Route::put('/absensi/{id}', [AbsensiController::class, 'update']);
    Route::delete('/absensi/{id}', [AbsensiController::class, 'destroy']);
    Route::post('absensi/{id}/keluar', [AbsensiController::class, 'storeKeluar']);

    // Rute untuk KaryawanController
    Route::get('/karyawan', [KaryawanController::class, 'index']);
    Route::post('/karyawan/created', [KaryawanController::class, 'store']);
    Route::put('/karyawan/{id}', [KaryawanController::class, 'update']);
    Route::delete('/karyawan/{id}', [KaryawanController::class, 'destroy']);

    // Rute untuk LemburController
    Route::post('/lembur', [LemburController::class, 'store']);
    Route::get('/lembur', [LemburController::class, 'index']);
    Route::get('/lembur/{id}', [LemburController::class, 'show']);
    Route::put('/lembur/{id}', [LemburController::class, 'update']);
    Route::delete('/lembur/{id}', [LemburController::class, 'destroy']);

    // Rute untuk CutiController
    Route::post('/izin', [IzinController::class, 'store']);
    Route::get('/izin', [IzinController::class, 'index']);
    Route::put('/izin/{id}', [IzinController::class, 'update']);
    Route::delete('/izin/{id}', [IzinController::class, 'destroy']);

    // Route Jabatan
    Route::get('/jabatan', [JabatanController::class, 'index']);
    Route::post('/jabatan', [JabatanController::class, 'store']);
    Route::get('/jabatan/{id}', [JabatanController::class, 'show']);
    Route::put('/jabatan/{id}', [JabatanController::class, 'update']);
    Route::delete('/jabatan/{id}', [JabatanController::class, 'destroy']);

    // Rute untuk Dinas Luar Kota
    Route::get('/dinas_luarkota', [DinasLuarKotaController::class, 'index']);
    Route::post('/dinas_luarkota', [DinasLuarKotaController::class, 'store']);
    Route::get('/dinas_luarkota/{id}', [DinasLuarKotaController::class, 'show']);
    Route::put('/dinas_luarkota/{id}', [DinasLuarKotaController::class, 'update']);
    Route::delete('/dinas_luarkota/{id}', [DinasLuarKotaController::class, 'destroy']);

    // Prefix Rute Karyawan
    // Route::prefix('karyawan')->group(function () {
    //     Route::get('/hadir/{idKaryawan}', [HadirController::class, 'getData']);
    //     Route::get('/cuti/{idKaryawan}', [CutiController::class, 'getData']);
    //     Route::get('/lembur/{idKaryawan}', [LemburController::class, 'getData']);
    //     Route::get('/dinas/{idKaryawan}', [DinasLuarKotaController::class, 'getData']);
    //     Route::get('/jabatan/{idKaryawan}', [JabatanController::class, 'getData']);
    // });

    // Route untuk TaskController
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::get('/tasks/{id}', [TaskController::class, 'show']);
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::put('/tasks/{id}', [TaskController::class, 'update']);
    Route::delete('/tasks/{id}', [TaskController::class, 'destroy']);

    Route::get('/payroll-summary/{id}', [PayrollController::class, 'getPayrollSummary']);
});