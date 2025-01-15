<?php

namespace App\Http\Controllers;

use App\Models\Izin;
use Illuminate\Http\Request;

class IzinController extends Controller
{
    public function index()
    {
        $izin = Izin::all();
        return response()->json($izin, 200); // Mengembalikan semua data izin sebagai JSON
    }

    public function store(Request $request)
    {
        // Validasi input request
        $request->validate([
            'id_karyawan' => 'required|exists:karyawans,id', // Harus ada di tabel karyawans
            'tgl_mulai' => 'required|date|before_or_equal:tgl_selesai',
            'tgl_selesai' => 'required|date|after_or_equal:tgl_mulai',
            'alasan' => 'required|string|max:255',
            'keterangan' => 'nullable|string|max:255',
            'status' => 'required|in:pending,disetujui,ditolak',
        ]);

        // Menghitung durasi secara otomatis
        $durasi = (new \Carbon\Carbon($request->tgl_selesai))->diffInDays(new \Carbon\Carbon($request->tgl_mulai)) + 1;

        $izin = Izin::create(array_merge($request->all(), ['durasi' => $durasi]));

        return response()->json([
            'message' => 'Izin berhasil ditambahkan.',
            'data' => $izin
        ], 201);
    }

    public function show($id)
    {
        $izin = Izin::find($id);
        if ($izin) {
            return response()->json($izin, 200); // Mengembalikan detail izin
        } else {
            return response()->json(['message' => 'Izin tidak ditemukan.'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        // Validasi input request
        $request->validate([
            'id_karyawan' => 'required|exists:karyawans,id',
            'tgl_mulai' => 'required|date|before_or_equal:tgl_selesai',
            'tgl_selesai' => 'required|date|after_or_equal:tgl_mulai',
            'alasan' => 'required|string|max:255',
            'keterangan' => 'nullable|string|max:255',
            'status' => 'required|in:pending,disetujui,ditolak',
        ]);

        // Menghitung durasi secara otomatis
        $durasi = (new \Carbon\Carbon($request->tgl_selesai))->diffInDays(new \Carbon\Carbon($request->tgl_mulai)) + 1;

        $izin = Izin::find($id);
        if ($izin) {
            $izin->update(array_merge($request->all(), ['durasi' => $durasi]));
            return response()->json([
                'message' => 'Izin berhasil diupdate.',
                'data' => $izin
            ], 200);
        } else {
            return response()->json(['message' => 'Izin tidak ditemukan.'], 404);
        }
    }

    public function destroy($id)
    {
        $izin = Izin::find($id);
        if ($izin) {
            $izin->delete();
            return response()->json(['message' => 'Izin berhasil dihapus.'], 200);
        } else {
            return response()->json(['message' => 'Izin tidak ditemukan.'], 404);
        }
    }
}
