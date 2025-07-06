<?php 

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DataKantorController extends Controller
{
    public function getDataKantor()
    {

        try {
            $data = DB::table('data_kantor')->orderBy('created_at', 'desc')->first();
            return response()->json([
                'message' => 'Login sukses.',
                'data' => $data
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Login gagal'], 500);
        }

    }

    
}
