<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Agen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgenController extends Controller
{
    public function index()
    {
        $branch = $this->branch;
        return view('pages/agen/index', compact('branch'));
    }

    public function getList(Request $request)
    {
        $data = Agen::select([
            'm_agen.*',
            'm_branch.name as branch',
            DB::raw("(SELECT COALESCE(COUNT(id), 0) as tjamaah FROM t_jamaah where t_jamaah.agen_id = m_agen.id) as tjamaah"),
            DB::raw("(SELECT COALESCE(SUM(publish_price - basic_price), 0) as fee FROM t_jamaah join m_paket on m_paket.id = t_jamaah.paket_id where t_jamaah.agen_id = m_agen.id) as fee"),
            DB::raw("(SELECT COALESCE(SUM(nominal), 0) as paid FROM t_payment where t_payment.agen_id = m_agen.id and t_payment.void_by IS NULL) as paidFee"),
        ])
            ->join('m_branch', "m_branch.id", "m_agen.fk_branch")
            ->orderBy('id', 'desc');

        if ($request->branch_id > 0) {
            $data->where('m_agen.fk_branch', $request->branch_id);
        }

        $data = $data->get();
        return response()->json(["message" => 'success', 'data' => $data], 200);
    }

    public function add()
    {
        $no = Agen::select(DB::raw('coalesce(max(id), 0) as maxId'))->first()->maxId + 1;
        $top = sprintf('%05s', $no);
        $branch = $this->branch;
        return view('pages/agen/add', compact('top', 'branch'));
    }

    public function saveAgen(Request $request)
    {
        $cek = Agen::where('kode_agen', $request->kode_agen)->first();
        if ($cek) {
            return response()->json(["error" => 'Kode Agen telah terdaftar, dimohon untuk menggantinya'], 400);
        }
        $insert = Agen::insert([
            'kode_agen' => $request->kode_agen,
            'nama' => $request->nama,
            'no_hp' => $request->no_hp,
            'no_ktp' => $request->noktp,
            'alamat' => $request->alamat,
            'nama_bank' => $request->nama_bank,
            'no_rek' => $request->no_rek,
            'is_active' => $request->is_active,
            'fk_branch' => $request->fk_branch,
        ]);
        if ($insert) {
            return response()->json(["message" => 'success', 'data' => $insert], 200);
        }
        return response()->json(["error" => 'Gagal input'], 400);
    }

    public function edit(Request $request)
    {
        $id = $request->id;
        $data = Agen::where('id', $request->id)->first();
        $branch = $this->branch;
        return view('pages/agen/edit', compact('data', 'id', 'branch'));
    }

    public function updateAgen(Request $request)
    {
        $check = Agen::where('id', $request->id)->first();
        if ($check) {
            $update = Agen::where('id', $request->id)->update([
                'kode_agen' => $request->kode_agen,
                'nama' => $request->nama,
                'no_hp' => $request->no_hp,
                'no_ktp' => $request->noktp,
                'alamat' => $request->alamat,
                'nama_bank' => $request->nama_bank,
                'no_rek' => $request->no_rek,
                'is_active' => $request->is_active,
                'fk_branch' => $request->fk_branch,
            ]);
            if ($update) {
                return response()->json(["message" => 'success', 'data' => $update], 200);
            }
            return response()->json(["error" => 'Tidak ada perubahan'], 400);
        }
        return response()->json(["error" => 'Agen Tidak ada'], 400);
    }
    public function delete(Request $request)
    {
        $check = Agen::where('id', $request->id)->first();
        if ($check) {
            $delete = Agen::where('id', $request->id)->delete();

            if ($delete) {
                return response()->json(["message" => 'success', 'data' => null], 200);
            }
            return response()->json(["error" => 'Tidak ada perubahan'], 400);
        }
        return response()->json(["error" => 'Agen Tidak ada'], 400);
    }

    public function feeAgen(Request $request)
    {
        $id = $request->id;
        $data = Agen::where('id', $request->id)->first();
        return view('pages/agen/feeAgent', compact('data', 'id'));
    }
}
