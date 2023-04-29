<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Country;
use Illuminate\Support\Facades\DB;

class Countries extends Seeder
{
    /**
     * Run the database seeders.
     *
     * @return void
     */
    public function run()
    {

        $c = new Country();
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $c->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $countries   = [];
        
        $countries[] = ['iso_code' => 'GB', 'name' => 'United Kingdom', 'country_code' => '44'];
        $countries[] = ['iso_code' => 'US', 'name' => 'United States', 'country_code' => '1'];
        
        foreach ($countries as $country) {
            Country::create([
                'name'         => $country['name'],
                'iso_code'     => $country['iso_code'],
                'country_code' => $country['country_code'],
                'status'       => true,
            ]);
        }
    }
}
