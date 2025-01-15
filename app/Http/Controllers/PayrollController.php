<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Izin;
use App\Models\DinasLuarKota;
use App\Models\Absensi;
use App\Models\Lembur;
use App\Models\Jabatan;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Karyawan;

class PayrollController extends Controller
{
    // Fungsi untuk menghitung hari kerja dalam rentang tanggal (tidak termasuk Minggu dan hari libur nasional)
    protected function calculateWorkingDays($startDate, $endDate, $holidays = [])
    {
        $workingDays = 0;
        $currentDate = $startDate->copy();
        
        while ($currentDate->lte($endDate)) {
            // Hitung hanya jika bukan hari Minggu dan bukan hari libur nasional
            if (!$currentDate->isSunday() && !in_array($currentDate->format('Y-m-d'), $holidays)) {
                $workingDays++;
            }
            $currentDate->addDay();
        }
         
        return $workingDays;
    }

    // Fungsi untuk menghitung total bonus (kehadiran, dinas luar kota, lembur)
    protected function calculateTotalBonus($bonusPerKaryawan, $kehadiranCount, $dinasLuarKotaDays, $lemburHours)
    {
        // Misalkan bonus dihitung dengan rumus seperti berikut:
        $bonusKehadiran = $kehadiranCount * $bonusPerKaryawan; // Bonus kehadiran
        $bonusDinasLuarKota = $dinasLuarKotaDays * $bonusPerKaryawan; // Bonus dinas luar kota
        $bonusLembur = $lemburHours * $bonusPerKaryawan; // Bonus lembur (misalnya per jam lembur)

        // Total bonus adalah penjumlahan dari bonus kehadiran, dinas luar kota, dan lembur
        $totalBonus = $bonusKehadiran + $bonusDinasLuarKota + $bonusLembur;

        return $totalBonus;
    }

    // Tambahkan ini di controller Laravel Anda (misalnya, KaryawanController)
public function index()
{
    $allKaryawan = Karyawan::with('jabatan')->get();
    return response()->json($allKaryawan);
}

    // Mendapatkan rekap jumlah izin, cuti, dan dinas luar kota serta kehadiran untuk semua karyawan
    public function getPayrollSummary()
    {
        try {
            // Mendapatkan semua karyawan dari tabel Karyawan
            $allKaryawan = Karyawan::all();

            // Validasi jika tabel Karyawan kosong
            if ($allKaryawan->isEmpty()) {
                return response()->json(['error' => 'Tidak ada karyawan yang ditemukan'], 404);
            }

            // Mendapatkan bulan dan tahun saat ini
            $currentMonth = Carbon::now()->month;
            $currentYear = Carbon::now()->year;

            // Daftar hari libur nasional
            $holidays = [
                '2024-01-01', // Tahun Baru
                '2024-03-11', // Hari Raya Nyepi
                '2024-05-01', // Hari Buruh
                // Tambahkan hari libur lainnya di sini
            ];

            // Array untuk menyimpan rekap payroll setiap karyawan
            $payrollSummaries = [];

            // Iterasi melalui semua karyawan
            foreach ($allKaryawan as $karyawan) {
                $idKaryawan = $karyawan->id;
                $namaKaryawan = $karyawan->nama_karyawan;
                $jabatan = $karyawan->jabatan; // Ambil jabatan karyawan

                // Hitung jumlah hari izin dalam bulan ini
                $izinDays = Izin::where('id_karyawan', $idKaryawan)
                    ->where('alasan', 'IZIN')
                    ->whereMonth('tgl_mulai', $currentMonth)
                    ->whereYear('tgl_mulai', $currentYear)
                    ->get()
                    ->sum(fn ($izin) => $this->calculateWorkingDays(
                        Carbon::parse($izin->tgl_mulai),
                        Carbon::parse($izin->tgl_selesai),
                        $holidays
                    ));

                // Menghitung durasi dinas luar kota dalam bulan ini
                $dinasLuarKotaDays = DinasLuarKota::where('id_karyawan', $idKaryawan)
                    ->whereMonth('tgl_berangkat', $currentMonth)
                    ->whereYear('tgl_berangkat', $currentYear)
                    ->get()
                    ->sum(fn ($dinas) => $this->calculateWorkingDays(
                        Carbon::parse($dinas->tgl_berangkat),
                        Carbon::parse($dinas->tgl_kembali),
                        $holidays
                    ));

                // Hitung jumlah hari cuti dalam bulan ini
                $cutiDays = Izin::where('id_karyawan', $idKaryawan)
                    ->where('alasan', 'CUTI')
                    ->whereMonth('tgl_mulai', $currentMonth)
                    ->whereYear('tgl_mulai', $currentYear)
                    ->get()
                    ->sum(fn ($cuti) => $this->calculateWorkingDays(
                        Carbon::parse($cuti->tgl_mulai),
                        Carbon::parse($cuti->tgl_selesai),
                        $holidays
                    ));

                // Menghitung jumlah kehadiran unik (berdasarkan tanggal unik) dalam bulan ini
                $kehadiranCount = Absensi::where('id_karyawan', $idKaryawan)
                    ->whereMonth('tanggal', $currentMonth)
                    ->whereYear('tanggal', $currentYear)
                    ->distinct('tanggal') // Menghitung tanggal unik
                    ->count('tanggal'); // Menghitung jumlah tanggal unik

                // Menghitung total jam lembur dalam bulan ini
                $lemburHours = Lembur::where('id_karyawan', $idKaryawan)
                    ->whereMonth('tanggal_lembur', $currentMonth)
                    ->whereYear('tanggal_lembur', $currentYear)
                    ->sum('durasi_lembur');

                // Menyimpan hasil rekap per karyawan
                $payrollSummaries[] = [
                    'id_karyawan' => $idKaryawan,
                    'nama_karyawan' => $namaKaryawan,
                    'jabatan' => $jabatan->jabatan ?? 'Tidak Ada Jabatan',
                    'gaji_pokok' => $jabatan->gaji_pokok ?? 0,
                    'uang_kehadiran_perhari' => $jabatan->uang_kehadiran_perhari ?? 0,
                    'uang_makan' => $jabatan->uang_makan ?? 0,
                    'bonus' => $jabatan->bonus ?? 0,
                    'tunjangan' => $jabatan->tunjangan ?? 0,
                    'potongan' => $jabatan->potongan ?? 0,
                    'izin_count' => $izinDays,
                    'dinas_luar_kota_count' => $dinasLuarKotaDays,
                    'cuti_count' => $cutiDays,
                    'kehadiran_count' => $kehadiranCount,
                    'lembur_count' => $lemburHours,
                    'total_bonus' => $this->calculateTotalBonus(
                        $jabatan->bonus ?? 0,           // Bonus per karyawan
                        $kehadiranCount,                // Jumlah hari kehadiran
                        $dinasLuarKotaDays,             // Jumlah hari dinas luar kota
                        $lemburHours                    // Jumlah jam lembur
                    ),
                    // Hitung total gaji keseluruhan
                    'total_gaji' => ($jabatan->gaji_pokok ?? 0) + 
                ($kehadiranCount * ($jabatan->uang_kehadiran_perhari ?? 0)) + 
                ($dinasLuarKotaDays * ($jabatan->uang_makan ?? 0)) +
                $this->calculateTotalBonus(
                    $jabatan->bonus ?? 0,
                    $kehadiranCount,
                    $dinasLuarKotaDays,
                    $lemburHours
                ) + 
                ($jabatan->tunjangan ?? 0) -
                ($jabatan->potongan ?? 0), // Potongan
                ];
            }

            return response()->json(['payroll_summaries' => $payrollSummaries]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function generateSlipGaji(Request $request)
    {
        try {
            // Ambil user saat ini
            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            // Pastikan karyawan memiliki jabatan
            $jabatan = $user->jabatan; // Relasi ke tabel Jabatan
            if (!$jabatan) {
                return response()->json(['error' => 'Jabatan tidak ditemukan untuk karyawan ini'], 404);
            }

            // Panggil fungsi untuk mendapatkan rekap payroll summary
            $payrollSummary = $this->getPayrollSummary()->getData();

            if (isset($payrollSummary->error)) {
                return response()->json(['error' => $payrollSummary->error], 500);
            }

            // Ambil data total bonus dari payroll summary
            $totalBonus = $payrollSummary->total_bonus ?? 0;

            // Data untuk PDF
            $data = [
                'nama_karyawan' => $user->nama_karyawan,
                'nama_jabatan' => $jabatan->jabatan ?? 'Tidak Ada Jabatan',
                'bulan' => Carbon::now()->format('F'),
                'tahun' => Carbon::now()->year,
                'izin' => $payrollSummary->izin_count ?? 0,
                'cuti' => $payrollSummary->cuti_count ?? 0,
                'dinas_luar_kota' => $payrollSummary->dinas_luar_kota_count ?? 0,
                'kehadiran' => $payrollSummary->kehadiran_count ?? 0,
                'lembur' => $payrollSummary->lembur_count ?? 0,
                'total_bonus' => $totalBonus,
                'gaji_pokok' => $jabatan->gaji_pokok ?? 0,
                'tunjangan' => $jabatan->tunjangan ?? 0,
                'bonus' => $jabatan->bonus ?? 0,
                'potongan' => $jabatan->potongan ?? 0,
                'total_gaji' => $payrollSummary->total_gaji ?? 0, // Tambahkan total gaji di sini
            ];

            // Generate PDF
            $pdf = app('dompdf.wrapper');
            $pdf->loadView('pdf.slip_gaji', $data);

            // Simpan atau tampilkan PDF
            return $pdf->stream("slip_gaji_{$user->nama_karyawan}{$data['bulan']}{$data['tahun']}.pdf");
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
