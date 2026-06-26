<?php

namespace App\Http\Controllers\Klaim\Inap;

use App\Http\Controllers\Controller;
use App\Repositories\KlaimInap\KlaimInapRepository;

class KlaimController extends Controller
{
    protected $inapRepo;

    public function __construct(KlaimInapRepository $inapRepo)
    {
        $this->inapRepo = $inapRepo;
    }

    public function getKodeRegRJByInap($kode_reg_rbi)
    {
        $data = $this->inapRepo->getKodeRegRJByInap($kode_reg_rbi);
        return response()->json($data);
    }

    public function checkIsPersalinan($kode_reg_rbi)
    {
        $is_partus = $this->inapRepo->checkIsPersalinan($kode_reg_rbi);
        return response()->json((object)['is_partus' => $is_partus]);
    }
    
    public function cetakAll($kode_reg_rbi)
    {
        
    }
}
