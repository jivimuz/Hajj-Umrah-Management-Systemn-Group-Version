<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Agen;
use App\Models\Jamaah;
use App\Models\JamaahDocument;
use App\Models\MorePayment;
use App\Models\Paket;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JamaahController extends Controller
{
    private const COMMON_DOCUMENT_TYPES = [
        'Paspor',
        'KTP',
        'KK',
        'Buku Nikah',
        'Akte Kelahiran',
        'Foto',
        'Vaksin/Kesehatan',
        'Sertifikat Vaksin Meningitis',
        'Surat Keterangan Sehat',
        'Bukti Pendaftaran',
        'Dokumen Tambahan',
    ];

    private const HAJJ_DOCUMENT_TYPES = [
        'Dokumen Haji',
        'Bukti Setoran Awal',
        'Nomor Porsi',
        'Bukti Pelunasan Haji',
    ];

    public function index()
    {
        $branch = $this->branch;
        return view('pages/jamaah/index', compact('branch'));
    }

    public function documentsIndex()
    {
        $branch = $this->branch;
        return view('pages/jamaah/documents', compact('branch'));
    }

    public function getDocumentList(Request $request)
    {
        $jamaahQuery = Jamaah::select([
            't_jamaah.id',
            't_jamaah.kode_jamaah',
            't_jamaah.nama',
            'm_paket.type',
            'm_branch.name as branch',
            'm_paket.nama as paket',
        ])
            ->join('m_paket', 'm_paket.id', 't_jamaah.paket_id')
            ->join('m_branch', 'm_branch.id', 'm_paket.fk_branch')
            ->when($request->type, fn($query, $type) => $query->where('m_paket.type', $type))
            ->orderByDesc('t_jamaah.id');

        if ($request->branch_id > 0) {
            $jamaahQuery->where('m_paket.fk_branch', $request->branch_id);
        }

        $jamaah = $jamaahQuery->get();
        $documents = JamaahDocument::whereIn('jamaah_id', $jamaah->pluck('id'))
            ->get()
            ->groupBy('jamaah_id');

        $data = $jamaah->map(function ($item) use ($documents) {
            $itemDocuments = $documents->get($item->id, collect())->keyBy('document_type');
            $requiredTypes = $this->documentTypesFor($item->type);
            $status = [];
            $completed = 0;

            foreach (array_merge(self::COMMON_DOCUMENT_TYPES, self::HAJJ_DOCUMENT_TYPES) as $type) {
                $document = $itemDocuments->get($type);
                $applicable = in_array($type, $requiredTypes, true);
                $complete = (bool) ($document?->is_checked || $document?->file_path);
                $status[$type] = [
                    'id' => $document?->id,
                    'checked' => (bool) ($document?->is_checked),
                    'file' => (bool) ($document?->file_path),
                    'applicable' => $applicable,
                    'name' => $document?->original_name,
                    'preview' => $document?->file_path ? route('jamaah.documents.preview', $document) : null,
                ];
                $completed += $applicable && $complete ? 1 : 0;
            }

            return [
                'id' => $item->id,
                'kode_jamaah' => $item->kode_jamaah ?: '-',
                'nama' => $item->nama,
                'type' => $item->type,
                'branch' => $item->branch,
                'paket' => $item->paket,
                'documents' => $status,
                'completed' => $completed,
                'total_documents' => count($requiredTypes),
                'status' => $completed === count($requiredTypes) ? 'Lengkap' : 'Belum Lengkap',
            ];
        });

        return response()->json(['message' => 'success', 'data' => $data], 200);
    }

    public function documentForm(Request $request)
    {
        $jamaah = Jamaah::with('documents')->findOrFail($request->id);
        $documents = $jamaah->documents->keyBy('document_type');
        $paket = Paket::find($jamaah->paket_id);
        $documentTypes = $this->documentTypesFor($paket?->type);

        return view('pages/jamaah/document-form', compact('jamaah', 'documents', 'documentTypes'));
    }

    public function saveDocument(Request $request)
    {
        $request->validate([
            'jamaah_id' => ['required', 'integer', 'exists:t_jamaah,id'],
            'document_type' => ['required', 'in:' . implode(',', array_merge(self::COMMON_DOCUMENT_TYPES, self::HAJJ_DOCUMENT_TYPES))],
            'is_checked' => ['nullable', 'boolean'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $document = JamaahDocument::firstOrNew([
            'jamaah_id' => $request->jamaah_id,
            'document_type' => $request->document_type,
        ]);
        $document->is_checked = $request->boolean('is_checked') || $request->hasFile('document');
        $document->uploaded_by = Auth::id();

        if ($request->hasFile('document')) {
            if ($document->file_path) {
                File::delete(public_path($document->file_path));
            }

            $directory = public_path('images/jamaah/documents');
            File::ensureDirectoryExists($directory);
            $file = $request->file('document');
            $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $originalName = $file->getClientOriginalName();
            $mimeType = $file->getClientMimeType();
            $fileSize = $file->getSize();
            $file->move($directory, $fileName);
            $document->file_path = 'images/jamaah/documents/' . $fileName;
            $document->original_name = $originalName;
            $document->mime_type = $mimeType;
            $document->file_size = $fileSize;
        }

        $document->save();

        return response()->json(['message' => 'Dokumen berhasil disimpan', 'data' => $document], 200);
    }

    public function previewDocument(Request $request, JamaahDocument $document)
    {
        $absolutePath = realpath(public_path($document->file_path));
        $documentDirectory = realpath(public_path('images/jamaah/documents'));

        abort_unless($absolutePath && $documentDirectory && str_starts_with($absolutePath, $documentDirectory . DIRECTORY_SEPARATOR), 404);

        return response()->file($absolutePath, [
            'Content-Disposition' => 'inline; filename="' . addslashes($document->original_name ?: basename($absolutePath)) . '"',
        ]);
    }

    public function getList(Request $request)
    {
        $type = $request->type;
        $data = Jamaah::select([
            't_jamaah.*',
            'm_paket.nama as paket',
            'm_paket.type',
            'm_branch.name as branch',
            DB::raw("COALESCE(m_paket.publish_price,0) as price"),
            DB::raw("(SELECT COALESCE(SUM(nominal), 0) as paid FROM t_payment where t_payment.jamaah_id = t_jamaah.id and t_payment.void_by IS NULL) as paid"),
            DB::raw("(SELECT COALESCE(SUM(nominal), 0) as total FROM t_morepayment where t_morepayment.jamaah_id = t_jamaah.id) as morepayment")
        ])
            ->join('m_paket', 'm_paket.id', 't_jamaah.paket_id')
            ->join('m_branch', "m_branch.id", "m_paket.fk_branch")
            ->when($type, function ($q) use ($type) {
                return $q->where('m_paket.type', $type);
            })
            ->orderBy('t_jamaah.id', 'desc');

        if ($request->branch_id > 0) {
            $data->where('m_paket.fk_branch', $request->branch_id);
        }

        $data = $data->get();
        return response()->json(["message" => 'success', 'data' => $data], 200);
    }

    public function addUmrah()
    {

        $isHaji = false;
        $branch = $this->branch;
        return view('pages/jamaah/add', compact('isHaji', 'branch'));
    }

    public function addHaji()
    {

        $isHaji = true;
        $branch = $this->branch;
        return view('pages/jamaah/add', compact('isHaji', 'branch'));
    }

    public function getAgenList(Request $request)
    {
        $data = Agen::select(['m_agen.*'])->where('is_active', true);
        if ($request->fk_branch > 0) {
            $data->where('m_agen.fk_branch', $request->fk_branch);
        }
        if ($request->params) {
            $data->where('m_agen.nama', 'like  ', "%$request->params%");
        }
        $data = $data->get();
        return response()->json(["message" => 'success', 'data' => $data, 'val' => $request->paramsVal ?: null, 'title' => $request->paramsTitle ?: null, 'price' => $request->paramsPrice ?: null], 200);
    }


    public function getPaketList(Request $request)
    {
        $data = Paket::select(['m_paket.*', 'm_program.nama as program'])->join('m_program', 'm_program.id', 'm_paket.program_id')->join('m_branch', "m_branch.id", "m_paket.fk_branch")->where('m_paket.type', ($request->isHaji ? 'Haji' : 'Umrah'))->where('flight_date', '>=', date('Y-m-d'));
        if ($request->fk_branch > 0) {
            $data->where('m_paket.fk_branch', $request->fk_branch);
        }
        if ($request->params) {
            $data->where('m_paket.nama', 'like  ', "%$request->params%");
        }
        $data = $data->get();
        return response()->json(["message" => 'success', 'data' => $data, 'val' => $request->paramsVal ?: null, 'title' => $request->paramsTitle ?: null, 'price' => $request->paramsPrice ?: null], 200);
    }

    public function saveData(Request $request)
    {
        DB::beginTransaction();
        try {
            $paket = Paket::findOrFail($request->paket);
            $this->validateJamaahRequest($request);

            $check = Jamaah::where('no_ktp', $request->noktp)
                ->where('paket_id', $request->paket)
                ->first();

            if ($check) {
                DB::rollback();
                return response()->json(["error" => 'No KTP Jamaah sudah terdaftar di Paket ini'], 400);
            }

            $file_path = null;
            $upload_dir = 'images/jamaah';
            $file = null;
            $file_name = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment')[0];
                $file_name = uniqid() . '-' . $file->getClientOriginalName();
                $file_path = $upload_dir . '/' . $file_name;
            }

            $insert = Jamaah::create(array_merge([
                'nama' => $request->nama_jamaah,
                'paket_id' => $request->paket,
                'no_ktp' => $request->noktp,
                'no_hp' => $request->no_hp,
                'no_passport' => $request->no_passport,
                'passport_date' => $request->passport_date,
                'passport_expired' => $request->passport_expired,
                'city_passport' => $request->city_passport,
                'alamat' => $request->alamat,
                'agen_id' => $request->agen_id,
                'born_place' => $request->born_place,
                'born_date' => $request->born_date,
                'nama_ayah' => $request->nama_ayah,
                'nama_ibu' => $request->nama_ibu,
                'discount' => $request->discount,
                'gender' => $request->gender,
                'no_porsi' => $request->no_porsi ?: null,
                'regis_date' => $request->regis_date ?: null,
                'est_date' => $request->est_date ?: null,
                'vaccine1' => $request->vaccine1,
                'vaccine1_date' => $request->vaccine1_date,
                'vaccine2' => $request->vaccine2,
                'vaccine2_date' => $request->vaccine2_date,
                'vaccine3' => $request->vaccine3,
                'vaccine3_date' => $request->vaccine3_date,
                'attachment' => $file_path,
                'created_at' => Carbon::now()
            ], $this->requirementData($request, $paket)));

            $insert->update([
                'kode_jamaah' => $this->generateJamaahCode($paket->type, $insert->tanggal_daftar),
            ]);

            if ($file_path && $file) {
                $file->move(public_path($upload_dir), $file_name);
            }

            DB::commit();
            return response()->json(["message" => 'success', 'data' => $insert], 200);
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(["message" => 'error', 'data' => null, 'error' => $th->getMessage()], 400);
        }
    }


    public function edit(Request $request)
    {
        $id = $request->id;
        $data = Jamaah::where('id', $request->id)->first();
        $paket = Paket::select(['m_paket.*', 'm_program.nama as program'])->join('m_program', 'm_program.id', 'm_paket.program_id')->where('m_paket.id', $data->paket_id)->first();
        $agen = Agen::where('is_active', true)->where('fk_branch', $paket->fk_branch)->get();
        $isHaji = (int) $request->isHaji;
        $branch = $this->branch;
        return view('pages/jamaah/edit', compact('data', 'id', 'paket', 'agen', 'isHaji', 'branch'));
    }


    public function updateData(Request $request)
    {
        try {
            DB::beginTransaction();
            $check = Jamaah::where('id', $request->id)->first();
            if ($check) {
                $paket = Paket::findOrFail($request->paket);
                $this->validateJamaahRequest($request);
                $file_path = $check->attachment;
                $upload_dir = 'images/jamaah';
                $file = null;
                $file_name = null;
                if ($request->file()) {
                    if (file_exists($check->attachment)) {
                        unlink($check->attachment);
                    }
                    $file = $request->file('attachment')[0];
                    $file_name = uniqid() . '-' . $file->getClientOriginalName();
                    $file_path = $upload_dir . '/' . $file_name;
                }

                $update = Jamaah::where('id', $request->id)->update(array_merge([
                    'nama' => $request->nama_jamaah,
                    'paket_id' => $request->paket,
                    'no_ktp' => $request->noktp,
                    'no_hp' => $request->no_hp,
                    'no_passport' => $request->no_passport,
                    'passport_date' => $request->passport_date,
                    'passport_expired' => $request->passport_expired,
                    'city_passport' => $request->city_passport,
                    'alamat' => $request->alamat,
                    'agen_id' => $request->agen_id,
                    'born_place' => $request->born_place,
                    'born_date' => $request->born_date,
                    'nama_ayah' => $request->nama_ayah,
                    'nama_ibu' => $request->nama_ibu,
                    'discount' => $request->discount,
                    'gender' => $request->gender,
                    'no_porsi' => $request->no_porsi ?: null,
                    'regis_date' => $request->regis_date ?: null,
                    'est_date' => $request->est_date ?: null,
                    'vaccine1' => $request->vaccine1,
                    'vaccine1_date' => $request->vaccine1_date,
                    'vaccine2' => $request->vaccine2,
                    'vaccine2_date' => $request->vaccine2_date,
                    'vaccine3' => $request->vaccine3,
                    'vaccine3_date' => $request->vaccine3_date,
                    'attachment' => $file_path,
                    'updated_at' => Carbon::now()
                ], $this->requirementData($request, $paket)));

                if (!$check->kode_jamaah) {
                    Jamaah::where('id', $request->id)->update([
                        'kode_jamaah' => $this->generateJamaahCode($paket->type, $request->tanggal_daftar ?: $check->tanggal_daftar),
                    ]);
                }

                if ($request->file() && $update && $file) {
                    $file->move(public_path($upload_dir), $file_name);
                }
                if ($update) {
                    DB::commit();
                    return response()->json(["message" => 'success', 'data' => $update], 200);
                }
            }
            return response()->json(["error" => 'Gagal input'], 400);
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(["message" => 'error', 'data' => null, 'error' => $th->getMessage()], 400);
        }
    }

    public function delete(Request $request)
    {
        $check = Jamaah::where('id', $request->id)->first();
        if ($check) {
            $delete = Jamaah::where('id', $request->id)->delete();

            if ($delete) {
                return response()->json(["message" => 'success', 'data' => null], 200);
            }
            return response()->json(["error" => 'Tidak ada perubahan'], 400);
        }
        return response()->json(["error" => 'Jamaah Tidak ada'], 400);
    }

    private function validateJamaahRequest(Request $request): void
    {
        $request->validate([
            'nama_jamaah' => ['required', 'string', 'max:150'],
            'noktp' => ['required', 'digits:16'],
            'no_kk' => ['nullable', 'digits:16'],
            'born_date' => ['nullable', 'date'],
            'email' => ['nullable', 'email', 'max:150'],
            'kode_pos' => ['nullable', 'digits_between:5,10'],
            'passport_expired' => ['nullable', 'date'],
            'tanggal_daftar' => ['required', 'date'],
            'tanggal_keberangkatan' => ['nullable', 'date'],
        ]);
    }

    private function requirementData(Request $request, Paket $paket): array
    {
        return [
            'tanggal_daftar' => $request->tanggal_daftar ?: Carbon::today()->toDateString(),
            'jenis_ibadah' => $paket->type,
            'no_kk' => $request->no_kk,
            'desa_kelurahan' => $request->desa_kelurahan,
            'kecamatan' => $request->kecamatan,
            'kabupaten_kota' => $request->kabupaten_kota,
            'provinsi' => $request->provinsi,
            'kode_pos' => $request->kode_pos,
            'email' => $request->email,
            'status_pernikahan' => $request->status_pernikahan,
            'pendidikan' => $request->pendidikan,
            'pekerjaan' => $request->pekerjaan,
            'sudah_memiliki_paspor' => $request->boolean('sudah_memiliki_paspor'),
            'gol_darah' => $request->gol_darah,
            'kebutuhan_khusus' => $request->kebutuhan_khusus ?: 'Tidak Ada',
            'nama_kontak_darurat' => $request->nama_kontak_darurat,
            'hubungan_kontak_darurat' => $request->hubungan_kontak_darurat,
            'no_hp_darurat' => $request->no_hp_darurat,
            'tanggal_keberangkatan' => $request->tanggal_keberangkatan ?: $paket->flight_date,
            'status_pendaftaran' => $request->status_pendaftaran ?: 'Terdaftar',
            'status_pembayaran' => $request->status_pembayaran,
            'keterangan' => $request->keterangan,
        ];
    }

    private function generateJamaahCode(string $type, string $date): string
    {
        $prefix = $type === 'Haji' ? 'HJJ' : 'UMR';
        $datePart = Carbon::parse($date)->format('ymd');
        $sequence = Jamaah::where('kode_jamaah', 'like', "{$prefix}-{$datePart}-%")->count() + 1;

        do {
            $code = sprintf('%s-%s-%04d', $prefix, $datePart, $sequence++);
        } while (Jamaah::where('kode_jamaah', $code)->exists());

        return $code;
    }

    private function documentTypesFor(?string $type): array
    {
        return array_merge(
            self::COMMON_DOCUMENT_TYPES,
            $type === 'Haji' ? self::HAJJ_DOCUMENT_TYPES : []
        );
    }

    public function payment(Request $request)
    {
        $id = $request->id;
        $data = Jamaah::select([
            't_jamaah.*',
            'm_paket.nama as paket',
            'm_paket.publish_price as price',
            'm_paket.type',
            'm_program.nama as program',
            DB::raw("COALESCE(m_paket.publish_price,0) as price"),
            DB::raw("(SELECT COALESCE(SUM(nominal), 0) as paid FROM t_payment where t_payment.jamaah_id = t_jamaah.id and t_payment.void_by IS NULL) as paid"),
            DB::raw("(SELECT COALESCE(SUM(nominal), 0) as total FROM t_morepayment where t_morepayment.jamaah_id = t_jamaah.id ) as morepayment")
        ])
            ->join('m_paket', 'm_paket.id', 't_jamaah.paket_id')
            ->join('m_program', 'm_program.id', 'm_paket.program_id')
            ->where('t_jamaah.id', $id)
            ->first();

        return view('pages/jamaah/payment', compact('id', 'data'));
    }

    public function getListPayment(Request $request)
    {
        $history = Payment::select(['t_payment.*', 't_jamaah.nama as jamaah', 'm_paket.nama as paket'])
            ->join('t_jamaah', 't_jamaah.id', 't_payment.jamaah_id')
            ->join('m_paket', 'm_paket.id', 't_jamaah.paket_id')
            ->where('t_jamaah.id', $request->id)
            ->whereNull('t_payment.void_by')
            ->orderBy('t_payment.id', 'desc')->get();
        $paidCheck = Payment::select(DB::raw('COALESCE(SUM(nominal), 0) as paid'))->where('jamaah_id', $request->id)->whereNull('void_by')->first()->paid;
        return response()->json(["message" => 'success', 'data' => $history, 'paid' => $paidCheck], 200);
    }

    public function morePayment(Request $request)
    {
        $id = $request->id;
        $data = MorePayment::where('jamaah_id', $id)->get();
        $jamaah = Jamaah::select([
            't_jamaah.nama',
            't_jamaah.is_done',
            'm_paket.nama as paket',
            'm_paket.publish_price as price',
            'm_paket.type',
            DB::raw("COALESCE(m_paket.publish_price,0) as price"),
            DB::raw("(SELECT COALESCE(SUM(nominal), 0) as paid FROM t_payment where t_payment.jamaah_id = t_jamaah.id and t_payment.void_by IS NULL) as paid"),
            DB::raw("(SELECT COALESCE(SUM(nominal), 0) as total FROM t_morepayment where t_morepayment.jamaah_id = t_jamaah.id) as morepayment")
        ])
            ->join('m_paket', 'm_paket.id', 't_jamaah.paket_id')
            ->where('t_jamaah.id', $id)
            ->first();

        return view('pages/jamaah/morePayment', compact('id', 'data', 'jamaah'));
    }

    function saveAddBiaya(Request $request)
    {
        try {
            DB::beginTransaction();
            $check = Jamaah::where('id', $request->id)->first();
            if ($check) {
                $insert = MorePayment::insert([
                    'jamaah_id' => $request->id,
                    'jamaah_name' => $request->nama,
                    'nominal' => $request->nominal ?: 0,
                    'remark' => $request->remark,
                ]);
                if ($insert) {
                    DB::commit();
                    return response()->json(["message" => 'success', 'data' => $insert], 200);
                }
            }
            return response()->json(["error" => 'Gagal input'], 400);
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(["message" => 'error', 'data' => null, 'error' => $th->getMessage()], 400);
        }
    }

    public function deleteMorePayment(Request $request)
    {
        $check = MorePayment::where('id', $request->id)->first();
        if ($check) {
            $delete = MorePayment::where('id', $request->id)->delete();

            if ($delete) {
                return response()->json(["message" => 'success', 'data' => null], 200);
            }
            return response()->json(["error" => 'Tidak ada perubahan'], 400);
        }
        return response()->json(["error" => 'Data Tidak ada'], 400);
    }

    public function jamaahListByParams(Request $request)
    {
        $data = Jamaah::select('t_jamaah.*')
            ->join('m_paket', 'm_paket.id', 't_jamaah.paket_id')
            ->join('m_branch', "m_branch.id", "m_paket.fk_branch")
            ->where('t_jamaah.nama', 'like', '%' . $request->params . '%')
            ->limit(20);

        if ($request->fk_branch > 0) {
            $data->where('m_paket.fk_branch', $request->fk_branch);
        }

        $data = $data->get();
        $selectTitle = $request->selectTitle ?: null;
        $selectVal = $request->selectVal ?: null;

        return response()->json(["message" => 'success', 'data' => $data, 'selectTitle' => $selectTitle, 'selectVal' => $selectVal], 200);
    }
}
