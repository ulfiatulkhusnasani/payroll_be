<?php 

namespace App\Http\Controllers;

use App\Models\GajiKaryawan;
use Illuminate\Http\Request;

class GajiKaryawanController extends Controller
{
    /**
     * Menampilkan daftar gaji karyawan.
     */
    public function index()
    {
        $gajiKaryawans = GajiKaryawan::all(); // Mengambil semua data gaji karyawan
        return response()->json($gajiKaryawans);
    }

    /**
     * Menyimpan data gaji karyawan yang baru.
     */
    public function store(Request $request)
    {
        // Logging data request
        \Log::info('Request Data (Store):', $request->all());

        $validated = $request->validate([
            'id_karyawan' => 'required|exists:karyawans,id', // Menjamin id_karyawan valid
            'nama_karyawan' => 'required|string|max:255',
            'hadir' => 'required|integer',
            'cuti' => 'required|integer',
            'lembur' => 'required|integer',
            'dinas_pulang_kota' => 'required|integer',
            'potongan' => 'required|numeric',
            'gaji_pokok' => 'required|numeric',
        ]);                

        $gajiKaryawan = GajiKaryawan::create($validated);

        return response()->json([
            'message' => 'Gaji karyawan berhasil disimpan.',
            'data' => $gajiKaryawan
        ], 201);  // Menggunakan kode status 201 untuk menunjukkan resource berhasil dibuat        
    }

    /**
     * Menampilkan data gaji karyawan berdasarkan ID.
     */
    public function show($id)
    {
        $gajiKaryawan = GajiKaryawan::findOrFail($id);
        return response()->json($gajiKaryawan);
    }

    /**
     * Memperbarui data gaji karyawan.
     */
    public function update(Request $request, $id)
    {
        // Logging data request
        \Log::info('Request Data (Update):', $request->all());

        $validated = $request->validate([
            'nama_karyawan' => 'required|string|max:255',
            'hadir' => 'required|integer',
            'cuti' => 'required|integer',
            'lembur' => 'required|integer',
            'dinas_pulang_kota' => 'required|integer',
            'potongan' => 'required|numeric',
            'gaji_pokok' => 'required|numeric',
        ]);

        $gajiKaryawan = GajiKaryawan::findOrFail($id); // Mencari data berdasarkan ID
        $gajiKaryawan->update($validated); // Memperbarui data karyawan dengan data yang divalidasi

        return response()->json([
            'message' => 'Gaji karyawan berhasil disimpan.',
            'data' => $gajiKaryawan
        ], 201);  // Menggunakan kode status 201 untuk menunjukkan resource berhasil dibuat        
    }

    /**
     * Menghapus data gaji karyawan.
     */
    public function destroy($id)
    {
        $gajiKaryawan = GajiKaryawan::findOrFail($id); // Mencari data berdasarkan ID
        $gajiKaryawan->delete(); // Menghapus data

        return response()->json([
            'message' => 'Data gaji karyawan berhasil dihapus.'
        ]);
    }
}
