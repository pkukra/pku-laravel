<?php

namespace App\Repositories\RM;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class RMAuditTrail
{
    /**
     * Save audit trail for RM
     * 
     * @param array $data
     * @return boolean
     */
    public function insert($data)
    {
        try {
            DB::table('audit_trail_rm')
                ->insert([
                    "object_id" => $data['object_id'],
                    "action_id" => $data['action_id'],
                    "user_id" => $data['user_id'],
                    "data" => json_encode($data['data']),
                ]);
        } catch (\Exception $e) {
            Log::error('RMAuditTrail insert err: ' . $e->getMessage());
        }
    }
}
