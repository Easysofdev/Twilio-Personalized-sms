<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentMethods;
use Illuminate\Support\Facades\DB;

class PaymentMethodsSeeder extends Seeder
{
        /**
         * Run the database seeders.
         *
         * @return void
         */
        public function run()
        {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                DB::table('payment_methods')->truncate();
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');

                $payment_gateways = [
                        [
                                'name'    => 'Stripe',
                                'type'    => 'stripe',
                                'options' => json_encode([
                                        'publishable_key' => 'pk_test_AnS4Ov8GS92XmHeVCDRPIZF4',
                                        'secret_key'      => 'sk_test_iS0xwfgzBF6cmPBBkgO13sjd',
                                        'environment'     => 'sandbox',
                                ]),
                                'status'  => true,
                        ],
                ];

                foreach ($payment_gateways as $gateway) {
                        PaymentMethods::create($gateway);
                }
        }
}
