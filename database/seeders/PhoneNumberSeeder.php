<?php

namespace Database\Seeders;

use App\Models\PhoneNumbers;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PhoneNumberSeeder extends Seeder
{
        /**
         * Run the database seeds.
         *
         * @return void
         */
        public function run()
        {

                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                DB::table('phone_numbers')->truncate();
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');

                $user = User::first();

                $phone_numbers = [
                        [
                                'user_id'          => $user->id,
                                'number'           => '8801721970168',
                                'status'           => 'available',
                                'capabilities'     => json_encode(['sms', 'voice', 'mms']),
                                'price'            => 5,
                                'billing_cycle'    => 'monthly',
                                'frequency_amount' => 1,
                                'frequency_unit'   => 'month',
                                'currency_id'      => 1,
                        ],
                        [
                                'user_id'          => $user->id,
                                'number'           => '8801521970168',
                                'status'           => 'available',
                                'price'            => 5,
                                'capabilities'     => json_encode(['sms', 'voice']),
                                'billing_cycle'    => 'yearly',
                                'frequency_amount' => 1,
                                'frequency_unit'   => 'year',
                                'currency_id'      => 1,
                        ],
                ];

                foreach ($phone_numbers as $number) {
                        PhoneNumbers::create($number);
                }
        }
}
