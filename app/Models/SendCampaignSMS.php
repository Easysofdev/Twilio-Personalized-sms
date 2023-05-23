<?php

namespace App\Models;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Database\Eloquent\Model;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class SendCampaignSMS extends Model
{

    /**
     *
     * send plain message
     *
     * @param $data
     *
     * @return array|Application|Translator|string|null
     */
    public function sendPlainSMS($data)
    {
        $phone          = $data['phone'];
        $sending_server = $data['sending_server'];
        $gateway_name   = $data['sending_server']->settings;
        $message        = null;
        $sms_type       = $data['sms_type'];
        $get_sms_status = $data['status'];

        if (isset($data['message'])) {
            $message = $data['message'];
        }

        if ($get_sms_status == null) {

            switch ($gateway_name) {

                case 'Twilio':
                    try {
                        $client       = new Client($sending_server->account_sid, $sending_server->auth_token);
                        $get_response = $client->messages->create($phone, [
                            'from'           => $data['sender_id'],
                            'body'           => $message,
                            'statusCallback' => route('dlr.twilio'),
                        ]);

                        if ($get_response->status == 'queued' || $get_response->status == 'accepted') {
                            $get_sms_status = 'Delivered|' . $get_response->sid;
                        } else {
                            $get_sms_status = $get_response->status . '|' . $get_response->sid;
                        }
                    } catch (ConfigurationException | TwilioException $e) {
                        $get_sms_status = $e->getMessage();
                    }
                    break;

                default:
                    $get_sms_status = __('locale.sending_servers.sending_server_not_found');
                    break;
            }
        }


        $reportsData = [
            'user_id'           => $data['user_id'],
            'to'                => $phone,
            'message'           => $message,
            'sms_type'          => $data['sms_type'],
            'status'            => $get_sms_status,
            'cost'              => $data['cost'],
            'sending_server_id' => $sending_server->id,
        ];

        if (isset($data['sender_id'])) {
            $reportsData['from'] = $data['sender_id'];
        }

        if (isset($data['campaign_id'])) {
            $reportsData['campaign_id'] = $data['campaign_id'];
        }

        if (isset($data['api_key'])) {
            $reportsData['api_key'] = $data['api_key'];
            $reportsData['send_by'] = 'api';
        } else {
            $reportsData['send_by'] = 'from';
        }

        $status = Reports::create($reportsData);

        if ($status) {
            return $status;
        }

        return __('locale.exceptions.something_went_wrong');
    }


    /**
     * send mms message
     *
     * @param $data
     *
     * @return array|Application|Translator|string|null
     */
    public function sendMMS($data)
    {
        $phone          = $data['phone'];
        $sending_server = $data['sending_server'];
        $gateway_name   = $data['sending_server']->settings;
        $message        = null;
        $get_sms_status = $data['status'];
        $media_url      = $data['media_url'];

        if (isset($data['message'])) {
            $message = $data['message'];
        }
        if ($get_sms_status == null) {
            switch ($gateway_name) {

                case 'Twilio':

                    try {
                        $client = new Client($sending_server->account_sid, $sending_server->auth_token);

                        $get_response = $client->messages->create($phone, [
                            'from'     => $data['sender_id'],
                            'body'     => $message,
                            'mediaUrl' => $media_url,
                        ]);

                        if ($get_response->status == 'queued' || $get_response->status == 'accepted') {
                            $get_sms_status = 'Delivered|' . $get_response->sid;
                        } else {
                            $get_sms_status = $get_response->status . '|' . $get_response->sid;
                        }
                    } catch (ConfigurationException | TwilioException $e) {
                        $get_sms_status = $e->getMessage();
                    }
                    break;

                default:
                    $get_sms_status = __('locale.sending_servers.sending_server_not_found');
                    break;
            }
        }


        $reportsData = [
            'user_id'           => $data['user_id'],
            'to'                => $phone,
            'message'           => $message,
            'sms_type'          => 'mms',
            'status'            => $get_sms_status,
            'cost'              => $data['cost'],
            'sending_server_id' => $sending_server->id,
            'media_url'         => $media_url,
        ];

        if (isset($data['sender_id'])) {
            $reportsData['from'] = $data['sender_id'];
        }

        if (isset($data['campaign_id'])) {
            $reportsData['campaign_id'] = $data['campaign_id'];
        }

        if (isset($data['api_key'])) {
            $reportsData['api_key'] = $data['api_key'];
            $reportsData['send_by'] = 'api';
        } else {
            $reportsData['send_by'] = 'from';
        }

        $status = Reports::create($reportsData);

        if ($status) {
            return $status;
        }

        return __('locale.exceptions.something_went_wrong');
    }
}
