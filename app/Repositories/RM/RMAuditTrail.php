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
                    "user_email" => $data['user_email'],
                    "user_id" => $data['user_id'],
                    "created_at" => $data['created_at'],
                    "data" => json_encode($data['data']),
                ]);
            return true;
        } catch (\Exception $e) {
            Log::error('RMAuditTrail insert err: ' . $e->getMessage());
            return false;
        }
    }
}
