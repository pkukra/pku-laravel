<?php

namespace App\Repositories\ICD;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ICDRepository
{
    protected $connection = 'sqlsrvsimrs';
    protected $table = 'ICD_ALERT';

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

    /**
     * List alert berdasarkan kode ICD
     */
    public function listAlert($code)
    {
        $baseQuery = DB::connection($this->connection)
            ->table($this->table)
            ->where('icd_code', $code);

        $total = (clone $baseQuery)->count();

        $data = $baseQuery
            ->select('*')
            ->orderBy('id', 'asc')
            ->get();

        Log::info("List alert for ICD code {$code}, total: {$total}");

        return (object)[
            'total' => $total,
            'data'  => $data,
        ];
    }

    /**
     * Simpan data baru
     *
     * @param string $icdCode
     * @param string $description
     * @param bool $isCodeWarning
     * @return int Inserted ID
     */
    public function saveAlert($icdCode, $description, $isCodeWarning = 0)
    {
        $user = Auth::user();

        try {
            $id = DB::connection($this->connection)
                ->table($this->table)
                ->insertGetId([
                    'icd_code' => $icdCode,
                    'description' => $description,
                    'is_code_warning' => $isCodeWarning,
                    'mdd' => now(),
                    'mdb' => $user->email,
                ]);

            Log::info("Inserted new ICD_ALERT with id {$id}, code: {$icdCode}");

            return $id;
        } catch (\Exception $e) {
            Log::error("Failed to insert ICD_ALERT: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update alert
     */
    public function updateAlert($id, $description, $isCodeWarning = null)
    {
        try {
            $data = [
                'description' => $description,
                'mdd' => now(),
            ];

            if (!is_null($isCodeWarning)) {
                $data['is_code_warning'] = $isCodeWarning;
            }

            $updated = DB::connection($this->connection)
                ->table($this->table)
                ->where('id', $id)
                ->update($data);

            Log::info("Updated ICD_ALERT id {$id} with data: " . json_encode($data));

            return $updated;
        } catch (\Exception $e) {
            Log::error("Failed to update ICD_ALERT id {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete alert
     */
    public function deleteAlert($id)
    {
        try {
            $deleted = DB::connection($this->connection)
                ->table($this->table)
                ->where('id', $id)
                ->delete();

            Log::info("Deleted ICD_ALERT id {$id}");

            return $deleted;
        } catch (\Exception $e) {
            Log::error("Failed to delete ICD_ALERT id {$id}: " . $e->getMessage());
            throw $e;
        }
    }
}
