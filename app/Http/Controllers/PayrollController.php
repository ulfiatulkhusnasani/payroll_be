<?php

namespace App\Http\Controllers;

use Throwable;
use Carbon\Carbon;
use App\Models\Izin;
use App\Models\Lembur;
use App\Models\Absensi;
use App\Models\Jabatan;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use App\Models\DinasLuarKota;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

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
    protected function calculateTotalBonus($bonusPerKaryawan, $kehadiranCount)
    {
        // Misalkan bonus dihitung dengan rumus seperti berikut:
        $bonusKehadiran = $kehadiranCount * $bonusPerKaryawan; // Bonus kehadiran


        // Total bonus adalah penjumlahan dari bonus kehadiran, dinas luar kota, dan lembur
        $totalBonus = $bonusKehadiran;

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
            $allKaryawan = DB::table('payroll as p')
                ->join('karyawans as karyawan', 'p.id_karyawan', '=', 'karyawan.id')
                ->leftJoin('karyawans as direktur', 'p.id_direktur', '=', 'direktur.id')
                ->leftJoin('users as u', 'karyawan.email', '=', 'u.email')
                ->leftJoin('users as ud', 'direktur.email', '=', 'ud.email')
                ->leftJoin('jabatan as j', 'karyawan.jabatan_id', '=', 'j.id')
                ->where('karyawan.status', 'active')
                ->select(
                    'karyawan.id',
                    'u.nama_karyawan',
                    'karyawan.nip',
                    'karyawan.nik',
                    'karyawan.email',
                    'karyawan.alamat',
                    'karyawan.status',
                    'karyawan.jabatan_id',
                    'karyawan.no_handphone',
                    'j.jabatan',
                    'p.gaji_pokok',
                    'p.id as id_payroll',
                    'p.uang_kehadiran',
                    'p.total_point_kehadiran',
                    'p.total_point_task',
                    'p.uang_makan',
                    'p.bonus',
                    'p.tunjangan',
                    'p.potongan',
                    'p.total_gaji',
                    'p.jumlah_izin',
                    'p.jumlah_cuti',
                    'p.jumlah_kehadiran',
                    'p.kinerja',
                    'p.tanggal',

                    'ud.nama_karyawan as nama_direktur',
                )
                ->orderBy('p.tanggal', 'desc')
                ->get();


            // Validasi jika tabel Karyawan kosong
            if ($allKaryawan->isEmpty()) {
                return response()->json(['message' => 'Tidak ada karyawan yang ditemukan'], 404);
            }

            $payrollSummaries = [];

            // Iterasi melalui semua karyawan
            foreach ($allKaryawan as $karyawan) {
                $idKaryawan = $karyawan->id;
                $namaKaryawan = $karyawan->nama_karyawan;
                // Menyimpan hasil rekap per karyawan

                $tanggal = Carbon::make($karyawan->tanggal);
                Carbon::setLocale('id'); // Pastikan menggunakan locale Bahasa Indonesia
                $bulan = $tanggal->translatedFormat('F');
                $tahun = $tanggal->year;

                $payrollSummaries[] = [
                    'id_karyawan' => $idKaryawan,
                    'nama_karyawan' => $namaKaryawan,
                    'jabatan' => $karyawan->jabatan ?? 'Tidak Ada Jabatan',
                    'gaji_pokok' => $karyawan->gaji_pokok ?? 0,
                    'uang_harian' => $karyawan->uang_kehadiran ?? 0,
                    'id_payroll' => $karyawan->id_payroll,
                    'bulan' => $bulan,
                    'tanggal' => $karyawan->tanggal,
                    'uang_makan' => $karyawan->uang_makan ?? 0,
                    'bonus' => $karyawan->bonus ?? 0,
                    'total_point_task' => $karyawan->total_point_task ?? 0,
                    'total_point_kehadiran' => $karyawan->total_point_kehadiran ?? 0,
                    'tunjangan' => $karyawan->tunjangan ?? 0,
                    'potongan' => $karyawan->potongan,
                    'izin_count' => $karyawan->jumlah_izin,
                    'cuti_count' => $karyawan->jumlah_cuti,
                    'kehadiran_count' => $karyawan->jumlah_kehadiran,
                    'total_gaji' => $karyawan->total_gaji, // Potongan
                    'slip_gaji' => [
                        'month' => "$bulan $tahun",
                        'name' => $namaKaryawan,
                        'position' => $karyawan->jabatan,
                        'employeeId' => $karyawan->nip,
                        'basicSalary' => $karyawan->gaji_pokok,
                        'allowances' => $karyawan->tunjangan,
                        'deductions' => $karyawan->potongan,
                        'netSalary' => $karyawan->total_gaji,
                        'dirName' => $karyawan->nama_direktur,
                        'performance' => $karyawan->kinerja,
                    ]
                ];
            }

            return response()->json(['payroll_summaries' => $payrollSummaries]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function generateSlipGaji(Request $request)
    {
        // Ambil user saat ini
        try {
            $tanggal = Carbon::make($request->tanggal);
            $bulan = $tanggal->month;
            $tahun = $tanggal->year;
            // Mendapatkan semua karyawan dari tabel Karyawan
            $karyawan = DB::table('payroll as p')
                ->join('karyawans as karyawan', 'p.id_karyawan', '=', 'karyawan.id')
                ->leftJoin('karyawans as direktur', 'p.id_direktur', '=', 'direktur.id')
                ->leftJoin('users as u', 'karyawan.email', '=', 'u.email')
                ->leftJoin('users as ud', 'direktur.email', '=', 'ud.email')
                ->leftJoin('jabatan as j', 'karyawan.jabatan_id', '=', 'j.id')
                ->where('karyawan.status', 'active')
                ->select(
                    'karyawan.id',
                    'u.nama_karyawan',
                    'karyawan.nip',
                    'karyawan.nik',
                    'karyawan.email',
                    'karyawan.alamat',
                    'karyawan.status',
                    'karyawan.jabatan_id',
                    'karyawan.no_handphone',
                    'j.jabatan',
                    'p.gaji_pokok',
                    'p.uang_kehadiran',
                    'p.uang_makan',
                    'p.total_point_kehadiran',
                    'p.total_point_task',
                    'p.bonus',
                    'p.tunjangan',
                    'p.potongan',
                    'p.total_gaji',
                    'p.jumlah_izin',
                    'p.jumlah_cuti',
                    'p.jumlah_kehadiran',
                    'p.kinerja',
                    'p.tanggal',

                    'ud.nama_karyawan as nama_direktur',
                )
                ->where('karyawan.email', $request->email)
                ->whereMonth('p.tanggal', $bulan)
                ->whereYear('p.tanggal', $tahun)
                ->orderBy('p.tanggal', 'desc')
                ->first();


            // Validasi jika tabel Karyawan kosong
            if (!$karyawan) {
                return response()->json(['message' => 'Slip gaji anda belum pulang di bulan ini'], 404);
            }

            $payrollSummaries = [];

            // Iterasi melalui semua karyawan
            $namaKaryawan = $karyawan->nama_karyawan;
            // Menyimpan hasil rekap per karyawan

            $tanggal = Carbon::make($karyawan->tanggal);
            Carbon::setLocale('id'); // Pastikan menggunakan locale Bahasa Indonesia
            $bulan = $tanggal->translatedFormat('F');
            $tahun = $tanggal->year;

            $payrollSummaries = [
                'month' => "$bulan $tahun",
                'name' => $namaKaryawan,
                'position' => $karyawan->jabatan,
                'employeeId' => $karyawan->nip,
                'basicSalary' => $karyawan->gaji_pokok,
                'allowances' => $karyawan->tunjangan,
                'deductions' => $karyawan->potongan,
                'netSalary' => $karyawan->total_gaji,
                'dirName' => $karyawan->nama_direktur,
                'performance' => $karyawan->kinerja,
            ];


            return response()->json($payrollSummaries);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            // Validasi
            $request->validate([
                'id_karyawan' => 'required|exists:karyawans,id',
                // 'id_direktur' => 'required|exists:karyawans,id',
                'tanggal' => 'required|date',
                'target_absensi' => 'required|integer',
                // 'target_produktivitas' => 'required|integer',
                'hari_produktif' => 'required|integer',
            ]);

            $tanggal = Carbon::make($request->tanggal);
            $bulan = $tanggal->month;
            $tahun = $tanggal->year;

            $payroll = DB::table('payroll')
                ->where('id_karyawan', $request->id_karyawan)
                ->whereMonth('tanggal', $bulan)
                ->whereYear('tanggal', $tahun)
                ->first();

            if ($payroll) {
                return response()->json(['message' => "Karyawan sudah dibuatkan slip gaji untuk bulan ini"], 500);
            }


            // Ambil data jabatan
            $data_jabatan = DB::table('karyawans as k')
                ->leftJoin('jabatan as j', 'j.id', 'k.jabatan_id')
                ->select('j.*')
                ->where('k.id', $request->id_karyawan)
                ->first();

            $point_kehadiran = 0;
            // Absensi
            $absensi = DB::table('absensi as a')
                ->where('a.id_karyawan', $request->id_karyawan)
                ->whereMonth('a.tanggal', $bulan)
                ->whereYear('a.tanggal', $tahun)
                ->get();

            foreach ($absensi as $a) {

                $point = 10;

                if ($a->status == 'Terlamabat') {
                    $point -= 5;
                }

                $point_kehadiran += $point;
            }

            // Izin dan Cuti
            $izin = DB::table('izin as a')
                ->where('a.id_karyawan', $request->id_karyawan)
                ->where('a.alasan', 'izin')
                ->whereMonth('a.tgl_mulai', $bulan)
                ->whereYear('a.tgl_mulai', $tahun)
                ->count();

            $cuti = DB::table('izin as a')
                ->where('a.id_karyawan', $request->id_karyawan)
                ->where('a.alasan', 'cuti')
                ->whereMonth('a.tgl_mulai', $bulan)
                ->whereYear('a.tgl_mulai', $tahun)
                ->count();

            // Produktivitas (total point)
            $produktivitas = 0;
            $tasks = DB::table('tasks as t')
                ->select('t.tgl_mulai', 't.tgl_selesai', 't.batas_penyelesaian', 't.point')
                ->where('t.id_karyawan', $request->id_karyawan)
                ->where('t.status', 'selesai')
                ->whereMonth('t.tgl_mulai', $bulan)
                ->whereYear('t.tgl_mulai', $tahun)
                ->get();

            $target_tasks = DB::table('tasks as t')
                ->select(DB::raw('IFNULL(SUM(t.point), 0) AS target_produktivitas'))
                ->where('t.id_karyawan', $request->id_karyawan)
                ->where('t.status', 'selesai')
                ->whereMonth('t.tgl_mulai', $bulan)
                ->whereYear('t.tgl_mulai', $tahun)
                ->groupBy('t.point')
                ->first();

            if (!$target_tasks || $target_tasks->target_produktivitas == 0) {
                return response()->json(['error' => 'Karyawan masih belum punya task'], 500);
            }


            foreach ($tasks as $t) {
                $tgl_selesai = Carbon::make($t->tgl_selesai);
                $batas_penyelesaian = Carbon::make($t->batas_penyelesaian);
                $point = (int) $t->point;

                if ($tgl_selesai->gt($batas_penyelesaian)) {
                    $selisih_hari = $tgl_selesai->diffInDays($batas_penyelesaian);
                    $point -= $selisih_hari * 5;
                }

                $produktivitas += $point;
            }


            $jumlah_hari_produktif = (int) $request->hari_produktif;
            $jumlah_kehadiran = (int) count($absensi);
            $jumlah_cuti = (int) $cuti;
            $jumlah_izin = (int) $izin;

            $gaji_pokok = (int) $data_jabatan->gaji_pokok;
            $uang_kehadiran = (int) $data_jabatan->uang_kehadiran_perhari;
            $uang_makan = (int) $data_jabatan->uang_makan;
            $tunjangan = (int) $data_jabatan->tunjangan;

            $potongan = 0;
            $total_uang_kehadiran = (int) $jumlah_kehadiran * $uang_kehadiran;
            $jumlah_tidak_hadir = (int) $jumlah_hari_produktif - $jumlah_kehadiran;

            if ($jumlah_tidak_hadir > 0) {
                $potongan = (int) $jumlah_tidak_hadir * $uang_kehadiran;
            }



            // Panggil API prediksi
            $DecisionTree = Http::post('http://127.0.0.1:9000/prediksi', [
                'kehadiran' => (int) $point_kehadiran,
                'target_kehadiran' => (int) $request->target_absensi,
                'produktivitas' => (int) $produktivitas,
                'target_produktivitas' => (int) $target_tasks->target_produktivitas ?? 0,
                'bonus_jabatan' => (int) $data_jabatan->bonus,
            ]);

            $resp = $DecisionTree->json();
            $kinerja = $resp['kinerja'];
            $bonus = $resp['bonus'];

            $total_gaji = ($gaji_pokok + $uang_makan + $tunjangan + $total_uang_kehadiran + $bonus) - $potongan;

            $dataInput = [
                'id_karyawan' => $request->id_karyawan,
                // 'id_direktur' => $request->id_direktur,
                'tanggal' => $tanggal,
                'kinerja' => $kinerja,
                'jumlah_kehadiran' => $jumlah_kehadiran,
                'jumlah_cuti' => $jumlah_cuti,
                'jumlah_izin' => $jumlah_izin,
                'total_point_kehadiran' => $point_kehadiran,
                'total_point_task' => $produktivitas,
                'gaji_pokok' => $gaji_pokok,
                'uang_kehadiran' => $total_uang_kehadiran,
                'uang_makan' => $uang_makan,
                'tunjangan' => $tunjangan,
                'bonus' => $bonus,
                'potongan' => $potongan,
                'total_gaji' => $total_gaji
            ];

            // Simpan ke database jika perlu
            DB::table('payroll')->insert($dataInput);
            return response()->json($dataInput);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage() . ' ' . $e->getLine()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            // Validasi
            $request->validate([
                'id_karyawan' => 'required|exists:karyawans,id',
                // 'id_direktur' => 'required|exists:karyawans,id',
                'tanggal' => 'required|date',
                'target_absensi' => 'required|integer',
                // 'target_produktivitas' => 'required|integer',
                'hari_produktif' => 'required|integer',
            ]);

            $tanggal = Carbon::make($request->tanggal);
            $bulan = $tanggal->month;
            $tahun = $tanggal->year;

            $payroll = DB::table('payroll')
                ->where('id_karyawan', $request->id_karyawan)
                ->whereMonth('tanggal', $bulan)
                ->whereYear('tanggal', $tahun)
                ->first();

            if ($payroll) {
                return response()->json(['message' => "Karyawan sudah dibuatkan slip gaji untuk bulan ini"], 500);
            }
            // Ambil data jabatan
            $data_jabatan = DB::table('karyawans as k')
                ->leftJoin('jabatan as j', 'j.id', 'k.jabatan_id')
                ->select('j.*')
                ->where('k.id', $request->id_karyawan)
                ->first();

            $point_kehadiran = 0;
            // Absensi
            $absensi = DB::table('absensi as a')
                ->where('a.id_karyawan', $request->id_karyawan)
                ->whereMonth('a.tanggal', $bulan)
                ->whereYear('a.tanggal', $tahun)
                ->get();

            foreach ($absensi as $a) {

                $point = 10;

                if ($a->status == 'Terlamabat') {
                    $point -= 5;
                }

                $point_kehadiran += $point;
            }

            // Izin dan Cuti
            $izin = DB::table('izin as a')
                ->where('a.id_karyawan', $request->id_karyawan)
                ->where('a.alasan', 'izin')
                ->whereMonth('a.tgl_mulai', $bulan)
                ->whereYear('a.tgl_mulai', $tahun)
                ->count();

            $cuti = DB::table('izin as a')
                ->where('a.id_karyawan', $request->id_karyawan)
                ->where('a.alasan', 'cuti')
                ->whereMonth('a.tgl_mulai', $bulan)
                ->whereYear('a.tgl_mulai', $tahun)
                ->count();

            // Produktivitas (total point)
            $produktivitas = 0;
            $tasks = DB::table('tasks as t')
                ->select('t.tgl_mulai', 't.tgl_selesai', 't.batas_penyelesaian', 't.point')
                ->where('t.id_karyawan', $request->id_karyawan)
                ->where('t.status', 'selesai')
                ->whereMonth('t.tgl_mulai', $bulan)
                ->whereYear('t.tgl_mulai', $tahun)
                ->get();

            $target_tasks = DB::table('tasks as t')
                ->select(DB::raw('IFNULL(SUM(t.point), 0) AS target_produktivitas'))
                ->where('t.id_karyawan', $request->id_karyawan)
                ->where('t.status', 'selesai')
                ->whereMonth('t.tgl_mulai', $bulan)
                ->whereYear('t.tgl_mulai', $tahun)
                ->groupBy('t.point')
                ->first();

            if (!$target_tasks || $target_tasks->target_produktivitas == 0) {
                return response()->json(['error' => 'Karyawan masih belum punya task'], 500);
            }


            foreach ($tasks as $t) {
                $tgl_selesai = Carbon::make($t->tgl_selesai);
                $batas_penyelesaian = Carbon::make($t->batas_penyelesaian);
                $point = (int) $t->point;

                if ($tgl_selesai->gt($batas_penyelesaian)) {
                    $selisih_hari = $tgl_selesai->diffInDays($batas_penyelesaian);
                    $point -= $selisih_hari * 5;
                }

                $produktivitas += $point;
            }


            $jumlah_hari_produktif = (int) $request->hari_produktif;
            $jumlah_kehadiran = (int) count($absensi);
            $jumlah_cuti = (int) $cuti;
            $jumlah_izin = (int) $izin;

            $gaji_pokok = (int) $data_jabatan->gaji_pokok;
            $uang_kehadiran = (int) $data_jabatan->uang_kehadiran_perhari;
            $uang_makan = (int) $data_jabatan->uang_makan;
            $tunjangan = (int) $data_jabatan->tunjangan;

            $potongan = 0;
            $total_uang_kehadiran = (int) $jumlah_kehadiran * $uang_kehadiran;
            $jumlah_tidak_hadir = (int) $jumlah_hari_produktif - $jumlah_kehadiran;

            if ($jumlah_tidak_hadir > 0) {
                $potongan = (int) $jumlah_tidak_hadir * $uang_kehadiran;
            }



            // Panggil API prediksi
            $DecisionTree = Http::post('http://127.0.0.1:9000/prediksi', [
                'kehadiran' => (int) $point_kehadiran,
                'target_kehadiran' => (int) $request->target_absensi,
                'produktivitas' => (int) $produktivitas,
                'target_produktivitas' => (int) $target_tasks->target_produktivitas ?? 0,
                'bonus_jabatan' => (int) $data_jabatan->bonus,
            ]);

            $resp = $DecisionTree->json();
            $kinerja = $resp['kinerja'];
            $bonus = $resp['bonus'];

            $total_gaji = ($gaji_pokok + $uang_makan + $tunjangan + $total_uang_kehadiran + $bonus) - $potongan;

            $dataInput = [
                'id_karyawan' => $request->id_karyawan,
                // 'id_direktur' => $request->id_direktur,
                'tanggal' => $tanggal,
                'kinerja' => $kinerja,
                'jumlah_kehadiran' => $jumlah_kehadiran,
                'jumlah_cuti' => $jumlah_cuti,
                'jumlah_izin' => $jumlah_izin,
                'total_point_kehadiran' => $point_kehadiran,
                'total_point_task' => $produktivitas,
                'gaji_pokok' => $gaji_pokok,
                'uang_kehadiran' => $total_uang_kehadiran,
                'uang_makan' => $uang_makan,
                'tunjangan' => $tunjangan,
                'bonus' => $bonus,
                'potongan' => $potongan,
                'total_gaji' => $total_gaji
            ];

            // Simpan ke database jika perlu
            DB::table('payroll')->where('id', $id)->update($dataInput);
            return response()->json($dataInput);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage() . ' ' . $e->getLine()], 500);
        }
    }

    
}
