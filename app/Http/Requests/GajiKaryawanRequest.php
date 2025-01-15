<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayrollRequest extends FormRequest
{
    public function authorize()
    {
        // Ubah ke true jika pengguna diizinkan
        return true;
    }

    public function rules()
    {
        return [
            'id_karyawan' => 'required|exists:karyawans,id',
            'hadir' => 'required|integer|min:0',
            'cuti' => 'required|integer|min:0',
            'lembur' => 'required|integer|min:0',
            'dinas_keluar_kota' => 'required|numeric|min:0',
            'potongan' => 'required|numeric|min:0',
            'gaji_pokok' => 'required|numeric|min:0',
        ];
    }

    public function messages()
    {
        return [
            'id_karyawan.required' => 'ID Karyawan wajib diisi.',
            'id_karyawan.exists' => 'ID Karyawan tidak ditemukan.',
            'hadir.required' => 'Jumlah hadir wajib diisi.',
            'hadir.integer' => 'Jumlah hadir harus berupa angka.',
            'cuti.required' => 'Jumlah cuti wajib diisi.',
            'lembur.required' => 'Jumlah lembur wajib diisi.',
            'dinas_keluar_kota.required' => 'Biaya dinas keluar kota wajib diisi.',
            'potongan.required' => 'Potongan wajib diisi.',
            'gaji_pokok.required' => 'Gaji pokok wajib diisi.',
        ];
    }
}
