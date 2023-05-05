<?php

namespace Database\Seeders;

use App\Models\SendingServer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SendingServerSeeder extends Seeder
{
        /**
         * Run the database seeds.
         *
         * @return void
         */
        public function run()
        {

                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                DB::table('sending_servers')->truncate();
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');

                $user = User::first();
                
                $sending_servers = [
                        [
                                'name'            => 'Twilio',
                                'user_id'         => $user->id,
                                'settings'        => 'Twilio',
                                'account_sid'     => 'account_sid',
                                'auth_token'      => 'auth_token',
                                'schedule'        => true,
                                'type'            => 'http',
                                'status'          => true,
                                'two_way'         => true,
                                'plain'           => true,
                                'mms'             => true,
                                'voice'           => true,
                                'whatsapp'        => true,
                                'sms_per_request' => 1,
                                'quota_value'     => 60,
                                'quota_base'      => 1,
                                'quota_unit'      => 'minute',
                        ],

                ];

                foreach ($sending_servers as $server) {
                        SendingServer::create($server);
                }
        }
}
