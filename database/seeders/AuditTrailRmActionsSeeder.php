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
            ['name' => 'add_diagnosis', 'created_at' => now()],
            ['name' => 'delete_diagnosis', 'created_at' => now()],
            ['name' => 'add_procedure', 'created_at' => now()],
            ['name' => 'delete_procedure', 'created_at' => now()],
            ['name' => 'change_sep', 'created_at' => now()],
            ['name' => 'bridging_data_inacbg', 'created_at' => now()],
            ['name' => 'final_data_inacbg', 'created_at' => now()],
        ];

        DB::table('audit_trail_rm_actions')->insert($actions);
    }
}
