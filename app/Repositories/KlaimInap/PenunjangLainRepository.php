<?php

namespace App\Repositories\KlaimInap;

use Illuminate\Support\Facades\DB;

class PenunjangLainRepository
{
    public function getByTransaksi($kode_reg)
    {
        return DB::connection('sqlsrvemr')
            ->table('VED_DOC_PENUNJANG')
            ->where('FRPNOTRANSAKSI', $kode_reg)
            ->orderBy('ID', 'desc')
            ->get();
    }

    public function store(array $data)
    {
        return DB::connection('sqlsrvemr')
            ->table('VED_DOC_PENUNJANG')
            ->insert($data);
    }

    public function findByIdAndTransaksi($id, $kode_reg)
    {
        return DB::connection('sqlsrvemr')
            ->table('VED_DOC_PENUNJANG')
            ->where('ID', $id)
            ->where('FRPNOTRANSAKSI', $kode_reg)
            ->first();
    }

    public function deleteByIdAndTransaksi($id, $kode_reg)
    {
        return DB::connection('sqlsrvemr')
            ->table('VED_DOC_PENUNJANG')
            ->where('ID', $id)
            ->where('FRPNOTRANSAKSI', $kode_reg)
            ->delete();
    }
}
