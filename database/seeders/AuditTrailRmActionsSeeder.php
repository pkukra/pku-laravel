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
            ['name' => 'add diagnosis', 'created_at' => now()],
            ['name' => 'delete diagnosis', 'created_at' => now()],
            ['name' => 'add procedure', 'created_at' => now()],
            ['name' => 'add procedure', 'created_at' => now()],
            ['name' => 'delete procedure', 'created_at' => now()],
            ['name' => 'change sep', 'created_at' => now()],
            ['name' => 'bridging data inacbg', 'created_at' => now()],
            ['name' => 'final data inacbg', 'created_at' => now()],
        ];

        DB::table('audit_trail_rm_actions')->insert($actions);
    }
}
