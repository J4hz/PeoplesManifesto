<?php

namespace Database\Seeders;

use App\Models\Constituency;
use App\Models\County;
use App\Models\Office;
use App\Models\Ward;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The pilot: Ruiru constituency in Kiambu County and its eight wards, with the
 * elected offices that answer for them. Leaders and their accounts are created
 * by an admin once each office holder has agreed to take part.
 *
 * To add a constituency or county, add it here or through the admin area. No code changes needed.
 */
class PilotAreaSeeder extends Seeder
{
    public function run(): void
    {
        $county = County::updateOrCreate(['slug' => 'kiambu'], ['name' => 'Kiambu', 'code' => 22]);

        $constituency = Constituency::updateOrCreate(
            ['slug' => 'ruiru'],
            ['county_id' => $county->id, 'name' => 'Ruiru'],
        );

        $wards = ['Biashara', 'Gatongora', 'Gitothua', 'Kahawa Sukari', 'Kahawa Wendani', 'Kiuu', 'Mwihoko', 'Mwiki'];

        foreach ($wards as $name) {
            $ward = Ward::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['constituency_id' => $constituency->id, 'name' => $name, 'is_active' => true],
            );

            // TODO: add the villages and estates of each ward (admin area, Wards page) from the
            // ward administrator's list. They drive the "areas" spread figure in ranking.

            Office::updateOrCreate(
                ['type' => 'mca', 'jurisdiction_type' => 'ward', 'jurisdiction_id' => $ward->id],
                ['name' => "MCA, {$name} Ward"],
            );
        }

        Office::updateOrCreate(
            ['type' => 'mp', 'jurisdiction_type' => 'constituency', 'jurisdiction_id' => $constituency->id],
            ['name' => 'MP, Ruiru Constituency'],
        );

        foreach (['governor' => 'Governor', 'senator' => 'Senator', 'women_rep' => 'Woman Representative'] as $type => $title) {
            Office::updateOrCreate(
                ['type' => $type, 'jurisdiction_type' => 'county', 'jurisdiction_id' => $county->id],
                ['name' => "{$title}, Kiambu County"],
            );
        }
    }
}
