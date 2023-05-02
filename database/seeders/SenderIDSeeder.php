<?php

namespace Database\Seeders;

use App\Models\Senderid;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SenderIDSeeder extends Seeder
{
        /**
         * Run the database seeds.
         *
         * @return void
         */
        public function run()
        {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                DB::table('senderid')->truncate();
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');

                $sender_ids = [
                        [
                                'user_id'          => 2,
                                'sender_id'        => 'USMS',
                                'status'           => 'active',
                                'price'            => 5,
                                'billing_cycle'    => 'yearly',
                                'frequency_amount' => '1',
                                'frequency_unit'   => 'year',
                                'currency_id'      => 1,
                        ],
                ];

                foreach ($sender_ids as $senderId) {
                        Senderid::create($senderId);
                }
        }
}
