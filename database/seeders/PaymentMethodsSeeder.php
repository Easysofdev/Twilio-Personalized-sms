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
                                        'publishable_key' => 'pk_test_51N27HdHtyLwpdT8M7pdpI6jq6AYAAt06FWhCsuZi3CUHWoMlLfiXMYbiBkfdtDPWlJdw7lYsc2GQ3NaYmlQoMZZt00scejwhSD',
                                        'secret_key'      => 'sk_test_51N27HdHtyLwpdT8M01CJ3k1L9E210CfuAsaswSfqffdK0RGFquW4nM9TgGL9GbpR3TXQj8L0HqD7g9shPdxIPnN9006JkZ8zfg',
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
