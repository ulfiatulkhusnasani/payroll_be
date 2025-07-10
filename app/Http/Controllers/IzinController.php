<?php

namespace App\Http\Controllers;

use App\Models\Izin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IzinController extends Controller
{
    public function index(Request $request)
    {
        $izin = DB::table('izin as i')->select('i.*', 'u.nama_karyawan')
            ->leftJoin('karyawans as k', 'i.id_karyawan', 'k.id')
            ->when($request->email, function ($query) use ($request) {
                $query->where('k.email', $request->email);
            })
            ->leftJoin('users as u', 'k.email', 'u.email')
            ->get();
        return response()->json($izin, 200);
    }

    public function userCutiPertahun(Request $request)
    {
        $izin = DB::table('izin as i')
            ->select('i.*', 'u.nama_karyawan')
            ->leftJoin('karyawans as k', 'i.id_karyawan', '=', 'k.id')
            ->leftJoin('users as u', 'k.email', '=', 'u.email')
            ->when($request->email, function ($query) use ($request) {
                $query->where('k.email', $request->email);
            })
            ->whereYear('i.created_at', now()->year) // <-- Perbaikan di sini
            ->where('i.status', 'disetujui')
            ->get();

        return response()->json($izin, 200);
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
            'lampiran' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ]);


        // Handle image update
        $imageBase64 = '';
        if ($request->hasFile('lampiran')) {
            $image = $request->file('lampiran');
            $imageBase64 = base64_encode(file_get_contents($image->getRealPath()));
        }



        // Hitung durasi
        $durasi = (new \Carbon\Carbon($request->tgl_selesai))->diffInDays($request->tgl_mulai) + 1;

        $izin = Izin::create(array_merge($request->all(), [
            'durasi' => $durasi,
            'lampiran' => $imageBase64
        ]));

        return response()->json([
            'message' => 'Izin berhasil ditambahkan.',
            'data' => $izin
        ], 201);
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
            'lampiran' => 'nullable|images|mimes:jpeg,png,jpg|max:5120',
        ]);

        // Menghitung durasi secara otomatis
        $durasi = (new \Carbon\Carbon($request->tgl_selesai))->diffInDays(new \Carbon\Carbon($request->tgl_mulai)) + 1;

        $izin = Izin::find($id);

        $imageBase64 = '';
        if ($request->hasFile('lampiran')) {
            $image = $request->file('lampiran');
            $imageBase64 = base64_encode(file_get_contents($image->getRealPath()));
        } else {
            $imageBase64 = $izin->lampiran;
        }


        if ($izin) {
            $izin->update(array_merge($request->all(), [
                'durasi' => $durasi,
                'lampiran' => $imageBase64
            ]));
            return response()->json([
                'message' => 'Izin berhasil diupdate.',
                'data' => $request->all()
            ], 200);
        } else {
            return response()->json(['message' => 'Izin tidak ditemukan.'], 404);
        }
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
