<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $tasks = DB::table('tasks as t')->select('t.*', 'u.nama_karyawan', 'j.jabatan')
            ->leftJoin('karyawans as k', 't.id_karyawan', 'k.id', )
            ->when($request->email, function ($query) use ($request) {
                $query->where('k.email', $request->email);
            })

            ->leftJoin('users as u', 'k.email', 'u.email')
            ->leftJoin('jabatan as j', 'k.jabatan_id', 'j.id')
            ->get();
        return response()->json($tasks);
    }

    public function show($id)
    {
        $task = Task::with('karyawan')->findOrFail($id);
        return response()->json($task);
    }

    public function store(Request $request)
    {

        try {

            $request->validate([
                'id_karyawan' => 'required|exists:karyawans,id',
                'judul_proyek' => 'required|string|max:255',
                'kegiatan' => 'required|string',
                'status' => 'required|in:belum dimulai,dalam progres,selesai',
                'tgl_mulai' => 'required|date',
                'batas_penyelesaian' => 'required|date',
                'tgl_selesai' => 'nullable|date',
                'point' => 'required|integer|min:0', // Validasi point
            ]);

            $task = Task::create([
                'id_karyawan' => $request->id_karyawan,
                'judul_proyek' => $request->judul_proyek,
                'kegiatan' => $request->kegiatan,
                'status' => $request->status,
                'tgl_mulai' => $request->tgl_mulai,
                'batas_penyelesaian' => $request->batas_penyelesaian,
                'tgl_selesai' => $request->tgl_selesai,
                'point' => $request->point, // Simpan point
            ]);

            $task->load('karyawan');
            return response()->json($task, 201);
        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->first()[0] ?? 'Validasi gagal';

            return response()->json([
                'message' => $firstError,
                'errors' => $firstError,
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan server',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {

        try {
            $request->validate([
                'id_karyawan' => 'required|exists:karyawans,id',
                'judul_proyek' => 'required|string|max:255',
                'kegiatan' => 'required|string',
                'status' => 'required|in:belum dimulai,dalam progres,selesai',
                'tgl_mulai' => 'required|date',
                'batas_penyelesaian' => 'required|date',
                'tgl_selesai' => 'nullable|date',
                'point' => 'required|integer|min:0', // Validasi point
            ]);

            $task = Task::findOrFail($id);

            $tgl_selesai = $request->tgl_selesai;

            if (!$request->tgl_selesai) {
                if ($request->status == 'selesai') {
                    $tgl_selesai = date('Y-m-d');
                }
            }

            $task->update([
                'id_karyawan' => $request->id_karyawan,
                'judul_proyek' => $request->judul_proyek,
                'kegiatan' => $request->kegiatan,
                'status' => $request->status,
                'tgl_mulai' => $request->tgl_mulai,
                'batas_penyelesaian' => $request->batas_penyelesaian,
                'tgl_selesai' => $tgl_selesai,
                'point' => $request->point, // Update point
                'status_approval' => $request->status_approval, // Update status_approval
            ]);

            $task->load('karyawan');
            return response()->json($task);
        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->first()[0] ?? 'Validasi gagal';

            return response()->json([
                'message' => $firstError,
                'errors' => $firstError,
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan server',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $task = Task::findOrFail($id);
        $task->delete();

        return response()->json(['message' => 'Task deleted successfully']);
    }
}
