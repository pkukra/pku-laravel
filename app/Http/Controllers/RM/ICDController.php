<?php

namespace App\Http\Controllers\RM;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Repositories\ICD\ICDRepository;

class ICDController extends Controller
{
    protected $icdRepo;

    public function __construct(ICDRepository $icdRepo)
    {
        $this->icdRepo = $icdRepo;
    }

    public function index()
    {
        return Inertia::render('RM/ICDALERT/Index');
    }

    public function index_data(Request $request)
    {
        $system = $request->get('system');
        $kode_icd = $request->get('kode_icd');
        $page = (int) $request->get('page', 1);
        $per_page = (int) $request->get('per_page', 20);

        $data = $this->icdRepo->listData($system, $kode_icd, $page, $per_page);
        return response()->json(['data' => $data]);
    }

    public function detail_icd_data($code)
    {

        $icdDetail = $this->icdRepo->getDetailByCode($code);
        return response()->json([
            'success' => true,
            'data' => $icdDetail,
        ]);
    }

    public function update_icd_warning($id, Request $request)
    {
        $request->validate([
            'is_code_warning' => 'required|in:0,1',
        ]);

        $updated = $this->icdRepo->updateWarning($id, $request->is_code_warning);
        if (!$updated) {
            return response()->json([
                'success' => false,
                'message' => 'Update gagal atau data tidak ditemukan',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status warning berhasil diperbarui',
        ]);
    }

    public function list_alert($code)
    {
        $data = $this->icdRepo->listAlert($code);
        return response()->json(['data' => $data]);
    }

    public function list_alert_by_codes(request $request)
    {
        $codes = $request->get('codes');
        if (!$codes) {
            return response()->json([
                'data' => [],
            ]);
        }
        $alerts = $this->icdRepo->listAlertByCodes($codes);
        return response()->json([
            'data' => $alerts,
        ]);
    }

    // ===== Tambahan baru =====

    public function save_alert(Request $request)
    {
        $request->validate([
            'icd_code' => 'required|string|max:10',
            'description' => 'required|string',
            'is_code_warning' => 'nullable|boolean',
        ]);

        $id = $this->icdRepo->saveAlert(
            $request->icd_code,
            $request->description,
            $request->is_code_warning ?? 0
        );

        return response()->json(['success' => true, 'id' => $id]);
    }

    public function update_alert(Request $request, $id)
    {
        $request->validate([
            'description' => 'required|string',
            'is_code_warning' => 'nullable|boolean',
        ]);

        $this->icdRepo->updateAlert(
            $id,
            $request->description,
            $request->is_code_warning ?? null
        );

        return response()->json(['success' => true]);
    }

    public function delete_alert($id)
    {
        $this->icdRepo->deleteAlert($id);
        return response()->json(['success' => true]);
    }
}
