<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use App\Models\Absensi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Izin; // Import the Izin model

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
    
    // Menampilkan semua karyawan dengan relasi jabatan
    public function index()
    {
        $karyawan = Karyawan::with('jabatan')->get();
        return response()->json($karyawan);
    }

    // Menyimpan karyawan baru
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_karyawan' => 'required|string|max:255',
            'nip' => 'digits_between:8,10|unique:karyawans,nip',
            'nik' => 'required|unique:karyawans,nik|max:16',
            'email' => 'required|email|unique:karyawans,email|max:255',
            'no_handphone' => 'required|string|max:15',
            'alamat' => 'required|string|max:255',
            'jabatan_id' => 'required|exists:jabatan,id',
            'password' => 'required|min:6',
            'device_code' => 'nullable|string|max:255|unique:karyawans,device_code',
            'avatar' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
                'message' => 'Validation failed',
            ], 422);
        }
    
        try {
            $karyawan = Karyawan::create([
                'nama_karyawan' => $request->nama_karyawan,
                'nip' => $request->nip,
                'nik' => $request->nik,
                'email' => $request->email,
                'no_handphone' => $request->no_handphone,
                'alamat' => $request->alamat,
                'jabatan_id' => $request->jabatan_id,
                'password' => Hash::make($request->password),
                'device_code' => $request->device_code,
                'avatar' => $request->avatar,
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

        $request->validate([
            'nama_karyawan' => 'required|string|max:255',
            'nip' => 'required|string|size:8|unique:karyawans,nip',
            'nik' => 'required|string|size:16|unique:karyawans,nik',
            'email' => 'required|email|unique:karyawans,email',
            'no_handphone' => 'required|string|min:10|max:15',
            'alamat' => 'required|string|max:500',
            'password' => 'required|string|min:6', // Hanya untuk tambah data
            'jabatan_id' => 'required|exists:jabatans,id',
            'device_code' => 'nullable|string|max:255',
        ]);        

        if ($request->filled('password')) {
            $validated['password'] = Hash::make($validated['password']);
        }

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