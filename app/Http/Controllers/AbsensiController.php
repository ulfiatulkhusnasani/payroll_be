<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Absensi;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class AbsensiController extends Controller
{
    // Menampilkan semua data absensi
    public function index()
    {
        try {
            $absensi = Absensi::with('karyawan')->get()->map(function ($absensi) {
                $absensi->tanggal = Carbon::parse($absensi->tanggal)->format('d-m-Y');
                $absensi->nama_karyawan = $absensi->karyawan ? $absensi->karyawan->nama_karyawan : null;

                // // Konversi Base64 untuk foto masuk
                // if ($absensi->foto_masuk) {
                //     $absensi->foto_masuk_base64 =  base64_encode($absensi->foto_masuk);
                // } else {
                //     $absensi->foto_masuk_base64 = null;
                // }

                return $absensi;
            });

            return response()->json($absensi, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal mengambil data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Menyimpan absensi baru
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'id_karyawan' => 'required|exists:karyawans,id',
                'tanggal' => 'required|date',
                'jam_masuk' => 'required|date_format:H:i',
                'foto_masuk' => 'required', // Foto masuk berupa longText
                'latitude_masuk' => 'required|numeric',
                'longitude_masuk' => 'required|numeric',
                'status' => 'required|string|in:Terlambat,Tepat waktu',
            ]);

            $validated['tanggal'] = Carbon::parse($validated['tanggal'])->format('Y-m-d');
            $absensi = Absensi::create($validated);

            $absensi->tanggal = Carbon::parse($absensi->tanggal)->format('d-m-Y');

            return response()->json([
                'message' => 'Absensi berhasil disimpan',
                'data' => $absensi
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Data tidak valid',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Data gagal disimpan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Menampilkan detail absensi berdasarkan ID
    public function show($id)
    {
        try {
            $absensi = Absensi::with('karyawan')->findOrFail($id);
            $absensi->tanggal = Carbon::parse($absensi->tanggal)->format('d-m-Y');

            // Konversi Base64 untuk foto masuk
            if ($absensi->foto_masuk) {
                $absensi->foto_masuk_base64 = base64_encode($absensi->foto_masuk);
            } else {
                $absensi->foto_masuk_base64 = null;
            }

            return response()->json($absensi, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Absensi tidak ditemukan',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    // Memperbarui data absensi
    public function update(Request $request, $id)
    {
        try {
            $absensi = Absensi::findOrFail($id);

            $validatedData = $request->validate([
                'id_karyawan' => 'exists:karyawans,id',
                'tanggal' => 'date',
                'jam_masuk' => 'nullable|date_format:H:i',
                'foto_masuk' => 'nullable', // Foto masuk sebagai longText
                'latitude_masuk' => 'nullable|numeric',
                'longitude_masuk' => 'nullable|numeric',
                'status' => 'nullable|string|in:Terlambat,Tepat waktu',
            ]);

            if (isset($validatedData['tanggal'])) {
                $validatedData['tanggal'] = Carbon::parse($validatedData['tanggal'])->format('Y-m-d');
            }

            $absensi->update($validatedData);

            $absensi->tanggal = Carbon::parse($absensi->tanggal)->format('d-m-Y');

            return response()->json([
                'message' => 'Absensi berhasil diperbarui',
                'data' => $absensi
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Data tidak valid',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Data gagal diperbarui',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Menghapus data absensi
    public function destroy($id)
    {
        try {
            Absensi::findOrFail($id)->delete();
            return response()->json([
                'message' => 'Absensi berhasil dihapus',
            ], 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menghapus data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function storeKeluar(Request $request, $id)
{
    try {
        $absensi = Absensi::findOrFail($id);

        // Validasi data
        $validated = $request->validate([
            'jam_keluar' => 'required|date_format:H:i',
            'foto_keluar' => 'required|string', // Menggunakan base64
            'latitude_keluar' => 'required|numeric',
            'longitude_keluar' => 'required|numeric',
        ]);

        // Konversi base64 untuk foto keluar
        if (isset($validated['foto_keluar']) && strpos($validated['foto_keluar'], 'data:image') === 0) {
            try {
                $imageData = base64_decode(explode(',', $validated['foto_keluar'])[1]);
                $filePath = 'absensi/' . $id . '_foto_keluar.jpg';
                Storage::disk('public')->put($filePath, $imageData);
                $validated['foto_keluar'] = $filePath;
            } catch (\Exception $e) {
                \Log::error('Gagal menyimpan foto keluar: ' . $e->getMessage());
                throw $e;
            }
        }

        // Format jam_keluar
        $validated['jam_keluar'] = Carbon::parse($validated['jam_keluar'])->format('H:i');

        // Update data absensi
        $absensi->update($validated);

        // Menambahkan base64 ke response
        $absensi->foto_keluar_base64 = $validated['foto_keluar'] ? base64_encode(Storage::disk('public')->get($validated['foto_keluar'])) : null;

        return response()->json([
            'message' => 'Absensi keluar berhasil disimpan',
            'data' => $absensi,
        ], 201);
    } catch (ValidationException $e) {
        return response()->json([
            'message' => 'Data tidak valid',
            'errors' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        \Log::error('Error storing absensi keluar: ' . $e->getMessage());

        return response()->json([
            'message' => 'Data gagal disimpan',
            'error' => $e->getMessage(),
        ], 500);
    }
}
}