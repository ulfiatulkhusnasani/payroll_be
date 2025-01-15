<?php

namespace App\Http\Controllers;

use App\Models\DinasLuarKota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DinasLuarKotaController extends Controller
{
    public function index()
    {
        try {
            $dinasLuarKota = DinasLuarKota::with('karyawan')->get();
            return response()->json($dinasLuarKota);
        } catch (\Exception $e) {
            Log::error('Error fetching data: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal mengambil data'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            Log::info('Memulai proses penyimpanan...');

            // Validasi data input
            $validatedData = $request->validate([
                'id_karyawan' => 'required|exists:karyawans,id',  // Validasi id_karyawan
                'tgl_berangkat' => 'required|date',
                'tgl_kembali' => 'required|date|after_or_equal:tgl_berangkat',
                'kota_tujuan' => 'required|string|max:255',
                'keperluan' => 'required|string|max:255',
                'biaya_transport' => 'required|numeric|min:0',
                'biaya_penginapan' => 'required|numeric|min:0',
                'uang_harian' => 'required|numeric|min:0',
                'status' => 'nullable|in:disetujui,ditolak,pending',  // Validasi status
            ]);

            $id_karyawan = $validatedData['id_karyawan'];  // Mengambil id_karyawan dari request

            // Hitung total biaya
            $totalBiaya = $validatedData['biaya_transport'] + $validatedData['biaya_penginapan'] +
                ($validatedData['uang_harian'] * $this->calculateDays($validatedData['tgl_berangkat'], $validatedData['tgl_kembali']));

            Log::info('Total biaya dihitung: ' . $totalBiaya);

            // Set status jika diberikan, jika tidak gunakan default 'pending'
            $status = $validatedData['status'] ?? 'pending';

            // Simpan data Dinas Luar Kota
            $dinasLuarKota = DinasLuarKota::create([
                'id_karyawan' => $id_karyawan,  // Menggunakan id_karyawan dari request
                'tgl_berangkat' => $validatedData['tgl_berangkat'],
                'tgl_kembali' => $validatedData['tgl_kembali'],
                'kota_tujuan' => $validatedData['kota_tujuan'],
                'keperluan' => $validatedData['keperluan'],
                'biaya_transport' => $validatedData['biaya_transport'],
                'biaya_penginapan' => $validatedData['biaya_penginapan'],
                'uang_harian' => $validatedData['uang_harian'],
                'total_biaya' => $totalBiaya,
                'status' => $status, // Menggunakan status yang diterima dari request
            ]);

            return response()->json(['message' => 'Dinas luar kota berhasil ditambahkan', 'data' => $dinasLuarKota], 201);
        } catch (\Exception $e) {
            Log::error('Terjadi error saat menyimpan data: ' . $e->getMessage());
            return response()->json([
                'error' => 'Gagal menyimpan data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function calculateDays($startDate, $endDate)
    {
        try {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
            return $end->diffInDays($start) + 1;
        } catch (\Exception $e) {
            Log::error('Error calculating days: ' . $e->getMessage());
            return 0;
        }
    }

    public function show($id)
    {
        try {
            $dinasLuarKota = DinasLuarKota::findOrFail($id);
            return response()->json($dinasLuarKota);
        } catch (\Exception $e) {
            Log::error('Error fetching data by ID: ' . $e->getMessage());
            return response()->json(['error' => 'Data tidak ditemukan'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            // Validasi data input
            $validatedData = $request->validate([
                'tgl_berangkat' => 'required|date',
                'tgl_kembali' => 'required|date|after_or_equal:tgl_berangkat',
                'kota_tujuan' => 'required|string|max:255',
                'keperluan' => 'required|string|max:255',
                'biaya_transport' => 'required|numeric|min:0',
                'biaya_penginapan' => 'required|numeric|min:0',
                'uang_harian' => 'required|numeric|min:0',
                'status' => 'nullable|in:disetujui,ditolak,pending',  // Validasi status
            ]);

            // Temukan data Dinas Luar Kota berdasarkan ID
            $dinasLuarKota = DinasLuarKota::findOrFail($id);

            // Update data
            $dinasLuarKota->update($validatedData);

            // Hitung total biaya
            $totalBiaya = $validatedData['biaya_transport'] + $validatedData['biaya_penginapan'] +
                ($validatedData['uang_harian'] * $this->calculateDays($validatedData['tgl_berangkat'], $validatedData['tgl_kembali']));

            // Perbarui total biaya
            $dinasLuarKota->total_biaya = $totalBiaya;

            // Set status jika diberikan, jika tidak gunakan status lama
            $dinasLuarKota->status = $validatedData['status'] ?? $dinasLuarKota->status;

            $dinasLuarKota->save();

            return response()->json(['message' => 'Data berhasil diperbarui', 'data' => $dinasLuarKota], 200);
        } catch (\Exception $e) {
            Log::error('Error updating data: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal memperbarui data'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            // Temukan dan hapus data Dinas Luar Kota berdasarkan ID
            $dinasLuarKota = DinasLuarKota::findOrFail($id);
            $dinasLuarKota->delete();
            return response()->json(['message' => 'Data berhasil dihapus'], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting data: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal menghapus data'], 500);
        }
    }
}
