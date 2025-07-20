<?php

namespace App\Http\Controllers;

use Log;
use Carbon\Carbon;
use App\Models\Absensi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log as FacadesLog;

class AbsensiController extends Controller
{
    // Menampilkan semua data absensi

    public function getuserabsensi(Request $request)
    {
        $email = $request->query('email');
        $absensi = DB::table('absensi as a')
            ->join('karyawans as k', 'a.id_karyawan', 'k.id')
            ->where('k.email', $email)
            ->get();

        return response()->json($absensi, 200);
    }

    public function getuserizin(Request $request)
    {
        $email = $request->query('email');
        $izin = DB::table('izin as a')
            ->join('karyawans as k', 'a.id_karyawan', 'k.id')
            ->where('k.email', $email)
            ->get();

        return response()->json($izin, 200);
    }

    public function index(Request $request)
    {
        try {
            $absensi = DB::table('absensi as a')
                ->join('karyawans as k', 'a.id_karyawan', 'k.id')
                ->leftJoin('users as u', 'k.email', 'u.email')
                ->select('a.*', 'u.nama_karyawan', 'u.email as email_karyawan')
                ->when($request->email, function ($query) use ($request) {
                    $query->where('k.email', $request->email);
                })
                ->get();

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
                'email_karyawan' => 'required|email',
                'tanggal' => 'required|date',
                'jam_masuk' => 'required|date_format:H:i',
                'foto_masuk' => 'required',
                'latitude_masuk' => 'required|numeric',
                'longitude_masuk' => 'required|numeric',
            ]);

            // Cari data karyawan
            $karyawan = DB::table('karyawans')
                ->where('email', $validated['email_karyawan'])
                ->first();

            // Ambil data kantor terbaru
            $dataKantor = DB::table('data_kantor')
                ->latest('created_at')
                ->first();

            if (!$dataKantor || !$karyawan) {
                return response()->json([
                    'message' => 'Data tidak ditemukan',
                    'error' => 'data tidak ditemukan',
                ], 404);
            }

            $jarak = $this->haversineDistance(
                $validated['latitude_masuk'],
                $validated['longitude_masuk'],
                $dataKantor->latitude_kantor,
                $dataKantor->longitude_kantor
            );

            if ($jarak <= 1000) {
                if ($jarak > 30) {
                    return response()->json([
                        'message' => 'Karyawan berada di luar radius 30 meter dari kantor jarak ' . round($jarak, 2) . ' meter',
                        'jarak_ditempuh' => round($jarak, 2) . ' meter'
                    ], 422);
                }
            }

            $jamMasukKantor = Carbon::createFromFormat('H:i:s', $dataKantor->jam_masuk);

            $jamMasukUser = Carbon::createFromFormat('H:i', $validated['jam_masuk']);
            $status = $jamMasukUser->greaterThan($jamMasukKantor) ? 'Terlambat' : 'Tepat Waktu';

            // Format tanggal
            $tanggal = Carbon::parse($validated['tanggal'])->format('Y-m-d');

            // Buat data absensi
            $absensi = Absensi::create([
                'id_karyawan' => $karyawan->id,
                'tanggal' => $tanggal,
                'jam_masuk' => $validated['jam_masuk'],
                'foto_masuk' => $validated['foto_masuk'],
                'latitude_masuk' => $validated['latitude_masuk'],
                'longitude_masuk' => $validated['longitude_masuk'],
                'status' => $status,
            ]);

            // Format response
            return response()->json([
                'message' => 'Absensi berhasil disimpan',
                'data' => [
                    ...$absensi->toArray(),
                    'tanggal' => Carbon::parse($absensi->tanggal)->format('d-m-Y'),
                    'status' => $status
                ]
            ], 201);
        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->first()[0] ?? 'Validasi gagal';

            return response()->json([
                'message' =>  $firstError,
                'errors' => $firstError,
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan server',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function storepulang(Request $request, $id)
    {
        try {
            $absensi = Absensi::findOrFail($id);

            // Validasi data
            $validated = $request->validate([
                'jam_pulang' => 'required|date_format:H:i',
                'foto_pulang' => 'required|string', // Menggunakan base64
                'latitude_pulang' => 'required|numeric',
                'longitude_pulang' => 'required|numeric',
            ]);

            // Ambil data kantor terbaru
            $dataKantor = DB::table('data_kantor')
                ->latest('created_at')
                ->first();

            if (!$dataKantor) {
                return response()->json([
                    'message' => 'Data tidak ditemukan',
                    'error' => 'data tidak ditemukan',
                ], 404);
            }

            $jarak = $this->haversineDistance(
                $validated['latitude_pulang'],
                $validated['longitude_pulang'],
                $dataKantor->latitude_kantor,
                $dataKantor->longitude_kantor
            );

            if ($jarak <= 1000) {
                if ($jarak > 30) {
                    return response()->json([
                        'message' => 'Karyawan berada di luar radius 30 meter dari kantor jarak ' . round($jarak, 2) . ' meter',
                        'jarak_ditempuh' => round($jarak, 2) . ' meter'
                    ], 422);
                }
            }


            // Update data absensi
            $absensi->update($validated);

            // Menambahkan base64 ke response
            $absensi->foto_pulang_base64 = $validated['foto_pulang'] ? base64_encode(Storage::disk('public')->get($validated['foto_pulang'])) : null;

            return response()->json([
                'message' => 'Absensi pulang berhasil disimpan',
                'data' => $absensi,
            ], 200);
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

    protected function haversineDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // in meters
        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        return $earthRadius * $angle;
    }

}
