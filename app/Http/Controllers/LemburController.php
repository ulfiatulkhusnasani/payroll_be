<?php 

namespace App\Http\Controllers;

use App\Models\Lembur;
use Illuminate\Http\Request;

class LemburController extends Controller
{
    // Menampilkan semua data lembur
    public function index()
    {
        $lembur = Lembur::with('karyawan')->get();
        return response()->json($lembur);
    }

    // Menambahkan data lembur baru
    public function store(Request $request)
{
    $validated = $request->validate([
        'id_karyawan' => 'required|exists:karyawans,id',
        'tanggal_lembur' => 'required|date',
        'jam_mulai' => 'required|date_format:H:i:s',
        'jam_selesai' => 'required|date_format:H:i:s|after:jam_mulai',
        'alasan_lembur' => 'nullable|string',
        'status' => 'sometimes|in:pending,disetujui,ditolak', // Validasi status
    ]);

    // Hitung durasi lembur secara otomatis (dalam jam)
    $start = strtotime($validated['jam_mulai']);
    $end = strtotime($validated['jam_selesai']);
    $validated['durasi_lembur'] = round(($end - $start) / 3600, 2); // Hitung selisih dalam jam dengan 2 desimal

    // Tentukan status berdasarkan request atau default ke 'pending'
    $validated['status'] = $request->input('status', 'pending');  // Jika status ada di request, pakai itu, jika tidak 'pending'

    $lembur = Lembur::create($validated);

    return response()->json(['message' => 'Data lembur berhasil ditambahkan', 'data' => $lembur], 201);
}

    // Menampilkan detail data lembur
    public function show($id)
    {
        $lembur = Lembur::with('karyawan')->find($id);
        if (!$lembur) {
            return response()->json(['message' => 'Data lembur tidak ditemukan'], 404);
        }

        return response()->json($lembur);
    }

    // Memperbarui data lembur
    public function update(Request $request, $id)
    {
        $lembur = Lembur::find($id);
        if (!$lembur) {
            return response()->json(['message' => 'Data lembur tidak ditemukan'], 404);
        }

        $validated = $request->validate([
            'id_karyawan' => 'sometimes|exists:karyawans,id',
            'tanggal_lembur' => 'sometimes|date',
            'jam_mulai' => 'sometimes|date_format:H:i:s',
            'jam_selesai' => 'sometimes|date_format:H:i:s|after:jam_mulai',
            'alasan_lembur' => 'nullable|string',
            'status' => 'sometimes|in:pending,disetujui,ditolak', // Validasi status
        ]);

        // Hitung durasi lembur jika jam_mulai dan jam_selesai diperbarui
        if (isset($validated['jam_mulai']) && isset($validated['jam_selesai'])) {
            $start = strtotime($validated['jam_mulai']);
            $end = strtotime($validated['jam_selesai']);
            $validated['durasi_lembur'] = round(($end - $start) / 3600, 2); // Hitung selisih dalam jam dengan 2 desimal
        }

        $lembur->update($validated);

        return response()->json(['message' => 'Data lembur berhasil diperbarui', 'data' => $lembur]);
    }

    // Menghapus data lembur
    public function destroy($id)
    {
        $lembur = Lembur::find($id);
        if (!$lembur) {
            return response()->json(['message' => 'Data lembur tidak ditemukan'], 404);
        }

        $lembur->delete();

        return response()->json(['message' => 'Data lembur berhasil dihapus']);
    }
}