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

    public function __construct(
        ICDRepository $icdRepo,
    ) {
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
}
