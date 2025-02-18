<?php

namespace App\Http\Controllers\RM;

use App\Http\Controllers\Controller;
use App\Repositories\RM\BridgingEKlaimRepository;

class EklaimController extends Controller
{
    protected $eKlaimRepo;
    public function __construct(BridgingEKlaimRepository $eKlaimRepo)
    {
        $this->eKlaimRepo = $eKlaimRepo;
    }

    public function index_data($no_sep)
    {
        // $response = $this->eKlaimRepo->getKlaimData($no_sep);
        return response()->json([
            'status' => "ok",
            // 'data' => json_decode($response),
        ]);
    }
}
