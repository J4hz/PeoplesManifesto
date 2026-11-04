<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PilotAreaSeeder::class);

        // Demo accounts and reports exist only on developer machines and demo sites, never in production.
        if (app()->environment(['local', 'testing']) || config('civic.demo.enabled')) {
            $this->call(DemoSeeder::class);
        }
    }
}
