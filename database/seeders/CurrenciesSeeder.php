<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use App\Models\Currency;
use App\Models\User;

class CurrenciesSeeder extends Seeder
{
    /**
     * Run the database seeders.
     *
     * @return void
     */
    public function run()
    {
        $user = User::first();
        
        $currency_data = [
            [
                'uid'     => uniqid(),
                'user_id' => $user->id,
                'name'    => 'US Dollar',
                'code'    => 'USD',
                'format'  => '${PRICE}',
                'status'  => true,
            ]
        ];

        foreach ($currency_data as $data) {
            Currency::create($data);
        }
    }
}
