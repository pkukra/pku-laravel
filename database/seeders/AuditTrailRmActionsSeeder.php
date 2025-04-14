<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AuditTrailRmActionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $actions = [
            ['id' => 1, 'name' => 'add_diagnosis', 'created_at' => now()],
            ['id' => 2, 'name' => 'delete_diagnosis', 'created_at' => now()],
            ['id' => 3, 'name' => 'add_procedure', 'created_at' => now()],
            ['id' => 4, 'name' => 'delete_procedure', 'created_at' => now()],
            ['id' => 5, 'name' => 'change_sep', 'created_at' => now()],
            ['id' => 6, 'name' => 'bridging_data_inacbg', 'created_at' => now()],
            ['id' => 7, 'name' => 'final_data_inacbg', 'created_at' => now()],
            ['id' => 8, 'name' => 'update_perawatan', 'created_at' => now()],
            ['id' => 9, 'name' => 'update_catatan_khusus', 'created_at' => now()],
            ['id' => 10, 'name' => 'update_casemix_ranap', 'created_at' => now()],
        ];

        DB::table('audit_trail_rm_actions')->insert($actions);
    }
}
