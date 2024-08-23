<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Agen;
use App\Models\Jamaah;
use App\Models\MorePayment;
use App\Models\Paket;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index()
    {
        $branch = $this->branch;
        return view('pages/payment/index', compact('branch'));
    }

    public function getList(Request $request)
    {
        $monthYear = $request->month; // '2024-08'
        list($year, $month) = explode('-', $monthYear);

        $data = Payment::select(['t_payment.*', 't_jamaah.nama as jamaah', 'm_agen.nama as agen', 'm_branch.name as branch', 'm_paket.nama as paket'])
            ->leftJoin('t_jamaah', 't_jamaah.id', 't_payment.jamaah_id')
            ->leftJoin('m_agen', 'm_agen.id', 't_payment.agen_id')
            ->leftJoin('m_paket', 'm_paket.id', 't_jamaah.paket_id')
            ->join('m_branch', "m_branch.id", "t_payment.fk_branch")
            ->whereYear('paid_at', $year)
            ->whereMonth('paid_at', $month)
            ->orderBy('t_payment.id', 'desc');

        if ($request->branch_id > 0) {
            $data->where('t_payment.fk_branch', $request->branch_id);
        }

        $data = $data->get();

        return response()->json(["message" => 'success', 'data' => $data], 200);
    }

    public function add()
    {
        $branch = $this->branch;
        return view('pages/payment/add', compact('branch'));
    }

    public function getJamaahList(Request $request)
    {
        $data = Jamaah::select([
            't_jamaah.*',
            'm_paket.nama as paket',
            'm_paket.publish_price',
            'm_paket.flight_date',
            'm_program.nama as program',
            DB::raw("(SELECT COALESCE(SUM(nominal), 0) as paid FROM t_payment where t_payment.jamaah_id = t_jamaah.id  and t_payment.void_by IS NULL) as paid"),
            DB::raw("(SELECT COALESCE(SUM(nominal), 0) as total FROM t_morepayment where t_morepayment.jamaah_id = t_jamaah.id) as morepayment")
        ])
            ->join('m_paket', 't_jamaah.paket_id', 'm_paket.id')
            ->join('m_program', 'm_paket.program_id', 'm_program.id')
            ->where('t_jamaah.is_done', false);
        if ($request->fk_branch > 0) {
            $data->where('m_paket.fk_branch', $request->fk_branch);
        }
        if ($request->params) {
            $data->where('t_jamaah.nama', 'like  ', "%$request->params%");
        }
        $data = $data->get();
        return response()->json(["message" => 'success', 'data' => $data, 'val' => $request->paramsVal ?: null, 'title' => $request->paramsTitle ?: null, 'price' => $request->paramsPrice ?: null], 200);
    }

    public function outTransaction()
    {
        $branch = $this->branch;
        return view('pages/payment/tabs-out', compact('branch'));
    }

    public function pengeluaran()
    {
        $branch = $this->branch;
        return view('pages/payment/tabs/pengeluaran', compact('branch'));
    }

    public function refund()
    {
        $branch = $this->branch;
        return view('pages/payment/tabs/refund', compact('branch'));
    }

    public function getJamaahHistory(Request $request)
    {
        $history = Payment::select(['t_payment.*', 't_jamaah.nama as jamaah', 'm_paket.nama as paket'])
            ->join('t_jamaah', 't_jamaah.id', 't_payment.jamaah_id')
            ->join('m_paket', 'm_paket.id', 't_jamaah.paket_id')
            ->where('t_jamaah.id', $request->id)
            ->orderBy('id', 'desc')->get();
        $paidCheck = Payment::select(DB::raw('COALESCE(SUM(nominal), 0) as paid'))->whereNull('t_payment.void_by')->where('jamaah_id', $request->id)->first()->paid;

        return response()->json(["message" => 'success', 'data' => $history, 'paid' => $paidCheck], 200);
    }

    public function saveData(Request $request)
    {
        try {
            DB::beginTransaction();
            $insert = false;
            if ($request->is_refund == 1) {
                $insert = Payment::insert([
                    'fk_branch' =>  $request->fk_branch ?: 0,
                    'agen_id' =>  $request->agen_id ?: 0,
                    'jamaah_id' => $request->jamaah_id ?: 0,
                    'jamaah_name' => $request->jamaah_name ?: '-',
                    'nominal' => (0 - $request->nominal),
                    'remark' => $request->remark,
                    'paid_at' => Carbon::now(),
                    'void_at' => null,
                    'void_by' => null,
                    'created_at' => Carbon::now(),
                    'created_by' => auth()->user()->id,
                ]);
            } else {
                $insert = Payment::insert([
                    'fk_branch' =>  $request->fk_branch ?: 0,
                    'agen_id' =>  $request->agen_id ?: 0,
                    'jamaah_id' => $request->jamaah_id ?: 0,
                    'jamaah_name' => $request->jamaah_name ?: '-',
                    'nominal' => $request->nominal,
                    'remark' => $request->remark,
                    'paid_at' => Carbon::now(),
                    'void_at' => null,
                    'void_by' => null,
                    'created_at' => Carbon::now(),
                    'created_by' => auth()->user()->id,
                ]);
            }

            if ($insert) {
                if ($request->jamaah_id) {
                    $check = Jamaah::where('id', $request->jamaah_id)->first();
                    if ($check) {
                        $priceCheck = Paket::select(DB::raw('COALESCE(publish_price, 0) AS price'))->where('id', $check->paket_id)->first();

                        $paidCheck = Payment::select(DB::raw('COALESCE(SUM(nominal), 0) as paid'))->where('jamaah_id', $request->jamaah_id)->whereNull('t_payment.void_by')->first();
                        $morePaymentCheck = MorePayment::select(DB::raw('COALESCE(SUM(nominal), 0) as total'))->where('jamaah_id', $request->jamaah_id)->first();
                        $is_done = false;
                        $donepaid_date = null;


                        if ($paidCheck->paid >= (($priceCheck->price + $morePaymentCheck->total) - $check->discount)) {
                            $is_done = true;
                            $donepaid_date = Carbon::now();
                        }

                        Jamaah::where('id', $request->jamaah_id)->update([
                            'is_firstpaid' => true,
                            'is_done' => $is_done,
                            'firstpaid_date' => $check->firstpaid_date ?: Carbon::now(),
                            'donepaid_date' =>  $check->donepaid_date ?: $donepaid_date,
                        ]);
                    }
                }
                DB::commit();
                return response()->json(["message" => 'success', 'data' => $insert], 200);
            }

            return response()->json(["error" => 'Gagal input'], 400);
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(["message" => 'error', 'data' => null, 'error' => $th->getMessage()], 400);
        }
    }


    public function cancelPayment(Request $request)
    {
        $check = Payment::where('id', $request->id)->first();

        if ($check) {
            if ($check->jamaah_id != 0) {
                $priceCheck = Paket::select(DB::raw('COALESCE(publish_price, 0) AS price'))->where('id', $check->paket_id)->first();
                $morePaymentCheck = MorePayment::select(DB::raw('COALESCE(SUM(nominal), 0) as total'))->where('jamaah_id', $check->jamaah_id)->first();
                $paidCheck = Payment::select(DB::raw('COALESCE(SUM(nominal), 0) as paid'))->where('jamaah_id', $check->jamaah_id)->whereNull('t_payment.void_by')->first();

                $is_done = false;
                if ($paidCheck->paid >= (($priceCheck->price + $morePaymentCheck->total) - $check->discount)) {
                    $is_done = true;
                }

                $is_firstpaid = false;
                if ($paidCheck->paid >= 0) {
                    $is_firstpaid = true;
                }
                Jamaah::where('id', $check->jamaah_id)->update([
                    'is_firstpaid' => $is_firstpaid,
                    'is_done' => $is_done,
                    'firstpaid_date' => $check->firstpaid_date ?: null,
                    'donepaid_date' =>  $check->donepaid_date ?: null,
                ]);
            }

            $cancel = Payment::where('id', $request->id)->update([
                'void_at' => Carbon::now(),
                'void_by' => auth()->user()->id,
            ]);

            if ($cancel) {
                return response()->json(["message" => 'success', 'data' => null], 200);
            }
            return response()->json(["error" => 'Tidak ada perubahan'], 400);
        }
        return response()->json(["error" => 'Data Tidak ada'], 400);
    }

    public function feeAgen()
    {
        $branch = $this->branch;
        return view('pages/payment/tabs/feeAgent', compact('branch'));
    }


    public function getAgenFee(Request $request)
    {
        $htp =    Agen::select([
            'dt.*',
            DB::raw("(SELECT COALESCE(COUNT(id), 0) as tjamaah FROM t_jamaah where t_jamaah.agen_id = m_agen.id) as tjamaah"),
            DB::raw("(SELECT COALESCE(SUM(nominal), 0) as paid FROM t_payment where t_payment.agen_id = m_agen.id and t_payment.void_by IS NULL) as paidFee"),
        ])
            ->leftJoin(DB::raw("(SELECT COALESCE(SUM(publish_price - basic_price), 0) as fee,agen_id,paket_id, m_paket.nama as paket  FROM t_jamaah join m_paket on m_paket.id = t_jamaah.paket_id where agen_id = '$request->id' group by agen_id,paket_id, m_paket.nama) as dt"), 'dt.agen_id', 'm_agen.id')
            ->where('m_agen.id', $request->id)->get();


        return response()->json(["message" => 'success', 'data' => $htp], 200);
    }
    public function getAgenHistory(Request $request)
    {
        $history = Payment::select([
            't_payment.*',
            'm_agen.nama as agen',
        ])
            ->join('m_agen', 'm_agen.id', 't_payment.agen_id')
            ->where('m_agen.id', $request->id)
            ->orderBy('id', 'desc')->get();


        return response()->json(["message" => 'success', 'data' => $history], 200);
    }
}
