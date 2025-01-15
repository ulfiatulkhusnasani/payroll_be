<?php 

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index()
    {
        $tasks = Task::with('karyawan')->get();
        return response()->json($tasks);
    }

    public function show($id)
    {
        $task = Task::with('karyawan')->findOrFail($id);
        return response()->json($task);
    }

    public function store(Request $request)
    {
        \Log::info($request->all());

        $request->validate([
            'id_karyawan' => 'required|exists:karyawans,id',
            'judul_proyek' => 'required|string|max:255',
            'kegiatan' => 'required|string',
            'status' => 'required|in:belum dimulai,dalam progres,selesai',
            'tgl_mulai' => 'required|date',
            'batas_penyelesaian' => 'required|date',
            'tgl_selesai' => 'nullable|date',
            'point' => 'required|integer|min:0', // Validasi point
            'status_approval' => 'required|in:pending,disetujui,ditolak', // Validasi status_approval
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
            'status_approval' => $request->status_approval, // Simpan status_approval
        ]);

        $task->load('karyawan');
        return response()->json($task, 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'id_karyawan' => 'required|exists:karyawans,id',
            'judul_proyek' => 'required|string|max:255',
            'kegiatan' => 'required|string',
            'status' => 'required|in:belum dimulai,dalam progres,selesai',
            'tgl_mulai' => 'required|date',
            'batas_penyelesaian' => 'required|date',
            'tgl_selesai' => 'nullable|date',
            'point' => 'required|integer|min:0', // Validasi point
            'status_approval' => 'required|in:pending,disetujui,ditolak', // Validasi status_approval
        ]);

        $task = Task::findOrFail($id);

        $task->update([
            'id_karyawan' => $request->id_karyawan,
            'judul_proyek' => $request->judul_proyek,
            'kegiatan' => $request->kegiatan,
            'status' => $request->status,
            'tgl_mulai' => $request->tgl_mulai,
            'batas_penyelesaian' => $request->batas_penyelesaian,
            'tgl_selesai' => $request->tgl_selesai,
            'point' => $request->point, // Update point
            'status_approval' => $request->status_approval, // Update status_approval
        ]);

        $task->load('karyawan');
        return response()->json($task);
    }

    public function destroy($id)
    {
        $task = Task::findOrFail($id);
        $task->delete();

        return response()->json(['message' => 'Task deleted successfully']);
    }
}
