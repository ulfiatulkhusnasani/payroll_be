<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Task;
use App\Models\Absensi;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\Izin; // Import the Izin model
use Illuminate\Database\Eloquent\SoftDeletes;

class KaryawanController extends Controller
{
    use SoftDeletes;

    public function izins()
    {
        return $this->hasMany(Izin::class, 'id_karyawan');
    }

    public function absensis()
    {
        return $this->hasMany(Absensi::class, 'id_karyawan');
    }

    public function deleteRelatedRecords()
    {
        // Delete related izin records
        $this->izins()->delete();

        // Delete related absensi records
        $this->absensis()->delete();

        return $this;
    }

    public function getuser()
    {
        $karyawan = DB::table('users')
            ->get();

        return response()->json($karyawan);
    }

    // Menampilkan semua karyawan dengan relasi jabatan
    public function index(Request $request)
    {
        $karyawan = DB::table('karyawans as k')
            ->leftJoin('users as u', 'k.email', 'u.email')
            ->leftJoin('jabatan as j', 'k.jabatan_id', 'j.id')
            ->leftJoin('izin as i', function ($join) {
                $join->on('k.id', '=', 'i.id_karyawan')
                ->where('i.status', 'disetujui')
                    ->whereBetween('i.created_at', [now()->startOfYear(), now()->endOfYear()]);
            })
            ->when($request->email, function ($query) use ($request) {
                $query->where('k.email', $request->email);
            })
            ->groupBy(
                'k.id',
                'u.nama_karyawan',
                'k.nip',
                'k.nik',
                'k.email',
                'k.alamat',
                'k.status',
                'k.jabatan_id',
                'j.jabatan',
                'k.no_handphone'
            )
            ->select(
                'k.id',
                'u.nama_karyawan',
                'k.nip',
                'k.nik',
                'k.email',
                'k.alamat',
                'k.status',
                'k.jabatan_id',
                'j.jabatan',
                'k.no_handphone',
                DB::raw('COUNT(i.id) as jumlah_cuti'),
                DB::raw('GREATEST(0, 12 - COUNT(i.id)) as sisa_cuti') // batas minimum 0
            )
            ->get();



        return response()->json($karyawan);
    }

    // Menyimpan karyawan baru
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nip' => 'digits_between:8,10|unique:karyawans,nip',
            'nik' => 'required|unique:karyawans,nik|max:16',
            'email' => 'required|email|unique:karyawans,email|max:255',
            'no_handphone' => 'required|string|max:15',
            'alamat' => 'required|string|max:255',
            'jabatan_id' => 'required|exists:jabatan,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
                'message' => 'Validation failed',
            ], 422);
        }

        try {
            $karyawan = Karyawan::create([
                'nip' => $request->nip,
                'nik' => $request->nik,
                'email' => $request->email,
                'no_handphone' => $request->no_handphone,
                'alamat' => $request->alamat,
                'jabatan_id' => $request->jabatan_id,
            ]);

            return response()->json(['message' => 'Karyawan created successfully', 'data' => $karyawan], 201);
        } catch (\Exception $e) {
            // Catch any exceptions and return a detailed error message
            return response()->json([
                'message' => 'An error occurred while creating the karyawan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function show($id)
    {
        $karyawan = Karyawan::with('jabatan')->findOrFail($id);
        $karyawan->jabatan = $karyawan->jabatan->jabatan;

        return response()->json($karyawan, 200);
    }

    // Memperbarui data karyawan
    public function update(Request $request, $id)
    {
        $karyawan = Karyawan::findOrFail($id);

        $validated = $request->validate([
            'nip' => [
                'required',
                'string',
                'size:8',
                Rule::unique('karyawans', 'nip')->ignore($karyawan->id),
            ],
            'nik' => [
                'required',
                'string',
                'size:16',
                Rule::unique('karyawans', 'nik')->ignore($karyawan->id),
            ],
            'email' => [
                'required',
                'email',
                Rule::unique('karyawans', 'email')->ignore($karyawan->id),
            ],
            'no_handphone' => 'required|string|min:10|max:15',
            'alamat' => 'required|string|max:500',
            'status' => 'required|string',
            'jabatan_id' => 'required|exists:jabatan,id',
        ]);

        $karyawan->update(array_filter($validated));

        return response()->json(['message' => 'Karyawan updated successfully', 'data' => $karyawan], 200);
    }

    public function destroy($id)
    {
        try {
            // Find the karyawan, will throw exception if not found
            $karyawan = Karyawan::findOrFail($id);

            // Delete related Izin records
            Izin::where('id_karyawan', $id)->delete();

            // Delete related Absensi records
            Absensi::where('id_karyawan', $id)->delete();

            Task::where('id_karyawan', $id)->delete();

            // Soft delete the karyawan
            $karyawan->delete();

            return response()->json(['message' => 'Data karyawan berhasil dihapus!'], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Gagal menghapus data karyawan',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
