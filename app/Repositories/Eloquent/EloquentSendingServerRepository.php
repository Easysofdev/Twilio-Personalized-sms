<?php

namespace App\Repositories\Eloquent;

use App\Exceptions\GeneralException;
use App\Models\CustomSendingServer;
use App\Models\SendingServer;
use App\Repositories\Contracts\SendingServerRepository;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

class EloquentSendingServerRepository extends EloquentBaseRepository implements SendingServerRepository
{

        /**
         * EloquentSendingServerRepository constructor.
         *
         * @param  SendingServer  $sendingServer
         *
         */
        public function __construct(SendingServer $sendingServer)
        {
                parent::__construct($sendingServer);
        }


        /**
         * Store Sending server
         *
         * @param  array  $input
         *
         * @return SendingServer|mixed
         *
         * @throws GeneralException
         */
        public function store(array $input): SendingServer
        {

                $insert_value = array_keys($this->allSendingServer()[$input['settings']]);

                /** @var SendingServer $sendingServer */
                $sendingServer = $this->make(Arr::only($input, $insert_value));

                $sendingServer->status  = true;
                $sendingServer->user_id = auth()->user()->id;

                if (!$this->save($sendingServer)) {
                        throw new GeneralException(__('locale.exceptions.something_went_wrong'));
                }

                return $sendingServer;
        }

        /**
         * @param  SendingServer  $sendingServer
         *
         * @return bool
         * @throws GeneralException
         */
        private function save(SendingServer $sendingServer): bool
        {
                if (!$sendingServer->save()) {
                        throw new GeneralException(__('locale.exceptions.something_went_wrong'));
                }

                return true;
        }

        /**
         * @param  CustomSendingServer  $customSendingServer
         *
         * @return bool
         * @throws GeneralException
         */
        private function saveCustom(CustomSendingServer $customSendingServer): bool
        {
                if (!$customSendingServer->save()) {
                        throw new GeneralException(__('locale.exceptions.something_went_wrong'));
                }

                return true;
        }

        /**
         * @param  SendingServer  $sendingServer
         * @param  array  $input
         *
         * @return SendingServer
         * @throws Exception|Throwable
         *
         * @throws Exception
         */
        public function update(SendingServer $sendingServer, array $input): SendingServer
        {
                if (!$sendingServer->update($input)) {
                        throw new GeneralException(__('locale.exceptions.something_went_wrong'));
                }

                return $sendingServer;
        }

        /**
         * @param  SendingServer  $sendingServer
         * @param  int|null  $user_id
         *
         * @return bool|null
         * @throws GeneralException
         * @throws Exception
         */
        public function destroy(SendingServer $sendingServer, int $user_id = null): bool
        {

                if ($user_id) {

                        //Delete sending server
                        if (!SendingServer::where('uid', $sendingServer->uid)->where('user_id', $user_id)->delete()) {
                                //throw exception if not deleted
                                throw new GeneralException(__('locale.exceptions.something_went_wrong'));
                        }
                } else {
                        $plans = SendingServer::with('plans')->get();

                        //Delete sending server
                        if (!$sendingServer->delete()) {
                                //throw exception if not deleted
                                throw new GeneralException(__('locale.exceptions.something_went_wrong'));
                        }

                        foreach ($plans as $plan) {
                                foreach ($plan->plans as $data) {
                                        $data->checkStatus();
                                }
                        }
                }


                return true;
        }

        /**
         * @param  array  $ids
         * @param  int|null  $user_id
         *
         * @return mixed
         * @throws Throwable
         */
        public function batchDestroy(array $ids, int $user_id = null): bool
        {

                if ($user_id) {
                        $sendingSevers = $this->query()->whereIn('uid', $ids)->where('user_id', $user_id)->cursor();
                        foreach ($sendingSevers as $sever) {
                                $sever->delete();
                        }
                } else {
                        DB::transaction(function () use ($ids) {
                                $sendingSevers = $this->query()->whereIn('uid', $ids)->cursor();
                                foreach ($sendingSevers as $sever) {
                                        if ($sever->delete()) {
                                                $plans = $sever::with('plans')->get();
                                                foreach ($plans as $plan) {
                                                        foreach ($plan->plans as $data) {
                                                                $data->checkStatus();
                                                        }
                                                }
                                        }
                                }
                        });
                }

                return true;
        }

        /**
         * @param  array  $ids
         *
         * @return mixed
         * @throws Exception|Throwable
         *
         */
        public function batchActive(array $ids): bool
        {
                DB::transaction(function () use ($ids) {
                        if ($this->query()->whereIn('uid', $ids)
                                ->update(['status' => true])
                        ) {
                                return true;
                        }

                        throw new GeneralException(__('locale.exceptions.something_went_wrong'));
                });

                return true;
        }

        /**
         * @param  array  $ids
         *
         * @return mixed
         * @throws Exception|Throwable
         *
         */
        public function batchDisable(array $ids): bool
        {
                DB::transaction(function () use ($ids) {
                        if ($this->query()->whereIn('uid', $ids)
                                ->update(['status' => false])
                        ) {
                                return true;
                        }

                        throw new GeneralException(__('locale.exceptions.something_went_wrong'));
                });

                return true;
        }

        public function sendTestSMS(SendingServer $sendingServer, array $input)
        {
                // TODO: Implement sendTestSMS() method.
        }

        /**
         * All Sending Servers
         *
         * @return array[]
         */
        public function allSendingServer(): array
        {
                return [
                        "Twilio" => [
                                'name'            => 'Twilio',
                                'settings'        => 'Twilio',
                                'account_sid'     => 'account_sid',
                                'auth_token'      => 'auth_token',
                                'schedule'        => true,
                                'type'            => 'http',
                                'two_way'         => true,
                                'plain'           => true,
                                'mms'             => true,
                                'sms_per_request' => 1,
                                'quota_value'     => 60,
                                'quota_base'      => 1,
                                'quota_unit'      => 'minute',

                        ],
                ];
        }


        /**
         * Store custom sending server
         *
         * @param  array  $input
         *
         * @return SendingServer|mixed
         * @throws GeneralException
         * @throws Exception
         */
        public function storeCustom(array $input): SendingServer
        {
                $sendingServerInput = [
                        'name',
                        'api_link',
                        'success_keyword',
                        'plain',
                        'schedule',
                        'quota_value',
                        'quota_base',
                        'quota_unit',
                        'sms_per_request',
                        'cutting_value',
                        'cutting_unit',
                        'cutting_logic'
                ];

                /** @var SendingServer $sendingServer */
                $sendingServer = $this->make(Arr::only($input, $sendingServerInput));

                $settings = ucfirst(preg_replace('/\s+/', '', $input['name']));

                $sendingServer->settings = $settings;
                $sendingServer->status   = true;
                $sendingServer->custom   = true;
                $sendingServer->user_id  = auth()->user()->id;

                if ($this->save($sendingServer)) {

                        $customServer        = new CustomSendingServer();
                        $customSendingServer = $customServer->make(Arr::only($input, [
                                'http_request_method',
                                'json_encoded_post',
                                'content_type',
                                'content_type_accept',
                                'character_encoding',
                                'ssl_certificate_verification',
                                'authorization',
                                'multi_sms_delimiter',
                                'username_param',
                                'username_value',
                                'password_param',
                                'password_value',
                                'password_status',
                                'action_param',
                                'action_value',
                                'action_status',
                                'source_param',
                                'source_value',
                                'source_status',
                                'destination_param',
                                'message_param',
                                'unicode_param',
                                'unicode_value',
                                'unicode_status',
                                'route_param',
                                'route_value',
                                'route_status',
                                'language_param',
                                'language_value',
                                'language_status',
                                'custom_one_param',
                                'custom_one_value',
                                'custom_one_status',
                                'custom_two_param',
                                'custom_two_value',
                                'custom_two_status',
                                'custom_three_param',
                                'custom_three_value',
                                'custom_three_status'
                        ]));

                        $customSendingServer->server_id = $sendingServer->id;

                        if (!$this->saveCustom($customSendingServer)) {
                                $sendingServer->delete();
                                throw new GeneralException(__('locale.exceptions.something_went_wrong'));
                        }

                        return $sendingServer;
                }
                throw new GeneralException(__('locale.exceptions.something_went_wrong'));
        }


        /**
         * @param  SendingServer  $sendingServer
         * @param  array  $input
         *
         * @return SendingServer
         * @throws Exception|Throwable
         *
         * @throws Exception
         */
        public function updateCustom(SendingServer $sendingServer, array $input): SendingServer
        {

                if ($sendingServer->update($input)) {
                        $customServer = CustomSendingServer::where('server_id', $sendingServer->id)->first();

                        if (!$customServer->update($input)) {
                                throw new GeneralException(__('locale.exceptions.something_went_wrong'));
                        }

                        return $sendingServer;
                }

                throw new GeneralException(__('locale.exceptions.something_went_wrong'));
        }
}
