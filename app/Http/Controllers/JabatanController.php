<?php

namespace App\Http\Controllers;

use App\Models\Jabatan;
use Illuminate\Http\Request;

class JabatanController extends Controller
{
    // Menampilkan semua data jabatan
    public function index()
    {
        $jabatan = Jabatan::all();
        return response()->json($jabatan);
    }

    // Menyimpan data jabatan baru
    public function store(Request $request)
    {
        // Validasi data input
        $request->validate([
            'jabatan' => 'required|string|max:255',
            'gaji_pokok' => 'required|numeric|min:0',
            'uang_kehadiran_perhari' => 'required|numeric|min:0',
            'uang_makan' => 'required|numeric|min:0',
            'bonus' => 'nullable|numeric|min:0',
            'tunjangan' => 'nullable|numeric|min:0',
            'potongan' => 'nullable|numeric|min:0',
        ]);

        // Membuat entitas Jabatan baru
        $jabatan = Jabatan::create($request->all());

        // Mengembalikan respon sukses
        return response()->json([
            'message' => 'Jabatan berhasil ditambahkan',
            'data' => $jabatan
        ], 201);
    }

    // Menampilkan data jabatan berdasarkan ID
    public function show($id)
    {
        try {
            $jabatan = Jabatan::findOrFail($id);
            return response()->json($jabatan);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Jabatan tidak ditemukan', 'error' => $e->getMessage()], 404);
        }
    }

    // Memperbarui data jabatan
    public function update(Request $request, $id)
    {
        // Validasi data input
        $request->validate([
            'jabatan' => 'sometimes|string|max:255',
            'gaji_pokok' => 'sometimes|numeric|min:0',
            'uang_kehadiran_perhari' => 'sometimes|numeric|min:0',
            'uang_makan' => 'sometimes|numeric|min:0',
            'bonus' => 'nullable|numeric|min:0',
            'tunjangan' => 'nullable|numeric|min:0',
            'potongan' => 'nullable|numeric|min:0',
        ]);

        try {
            $jabatan = Jabatan::findOrFail($id);
            $jabatan->update($request->all());

            return response()->json([
                'message' => 'Jabatan berhasil diupdate',
                'data' => $jabatan
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal memperbarui jabatan', 'error' => $e->getMessage()], 500);
        }
    }

    // Menghapus data jabatan
    public function destroy($id)
    {
        try {
            $jabatan = Jabatan::findOrFail($id);
            $jabatan->delete();

            return response()->json(['message' => 'Jabatan berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Jabatan tidak ditemukan', 'error' => $e->getMessage()], 404);
        }
    }
}
