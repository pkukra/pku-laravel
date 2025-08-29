<?php

namespace App\Repositories\ICD;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ICDRepository
{
    public function listData($system = null, $code = null, $page = 1, $per_page = 10)
    {
        $baseQuery = DB::connection('sqlsrvsimrs')
            ->table('ICD')
            ->when($code, function ($query, $code) {
                if (strpos($code, '.') !== false) {
                    // Kalau ada titik, cari pakai LIKE di kolom code
                    return $query->where('ICD.code', 'like', "%{$code}%");
                } else {
                    // Hilangkan leading zero supaya bisa match dengan code2 yang simpel
                    $normalized = ltrim($code, '0');
                    return $query->where('ICD.code2', 'like', "%{$normalized}%");
                }
            })
            ->when($system, function ($query, $system) {
                if ($system === 'all') {
                    return $query->whereIn('ICD.system', ['ICD_10_2010_IM', 'ICD_9CM_2010_IM']);
                }
                return $query->where('ICD.system', $system);
            })
            ->where('ICD.validcode', 1);

        $total = (clone $baseQuery)->count();

        $data = $baseQuery
            ->select('ICD.*')
            ->orderBy('ICD.id', 'asc')
            ->limit($per_page)
            ->offset(($page - 1) * $per_page)
            ->get();

        return (object)[
            'total' => $total,
            'data'  => $data,
        ];
    }
}
