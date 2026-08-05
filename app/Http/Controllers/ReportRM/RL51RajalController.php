<?php

namespace App\Http\Controllers\ReportRM;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Repositories\ReportRM\RL51RajalRepository;

class RL51RajalController extends Controller
{
    protected $repo;

    public function __construct(RL51RajalRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        return Inertia::render('ReportRM/RL51Rajal/Index');
    }

    public function index_data(Request $request)
    {
        $date_start = $request->get('date_start');
        $date_end = $request->get('date_end');
        $icd10 = $request->get('icd10');

        $data = $this->repo->getData($date_start, $date_end, $icd10);
        return response()->json(['data' => $data]);
    }

    public function all_penyakit_index()
    {
        return Inertia::render('ReportRM/RL51Rajal/AllPenyakitIndex');
    }

    public function all_penyakit_data(Request $request)
    {
        $date_start = $request->get('date_start');
        $date_end   = $request->get('date_end');

        $data = $this->repo->getAllPenyakitData($date_start, $date_end);

        return response()->json([
            'data' => $data
        ]);
    }
}
