<?php

namespace App\Models;

use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Database\Eloquent\Model;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;
use Twilio\TwiML\VoiceResponse;

class SendCampaignSMS extends Model
{

    /**
     * make normal message to unicode message
     *
     * @param $message
     *
     * @return string
     */
    private function sms_unicode($message): string
    {
        $hex1 = '';
        if (function_exists('iconv')) {
            $latin = @iconv('UTF−8', 'ISO−8859−1', $message);
            if (strcmp($latin, $message)) {
                $arr  = unpack('H*hex', @iconv('UTF-8', 'UCS-2BE', $message));
                $hex1 = strtoupper($arr['hex']);
            }
            if ($hex1 == '') {
                $hex2 = '';
                for ($i = 0; $i < strlen($message); $i++) {
                    $hex = dechex(ord($message[$i]));
                    $len = strlen($hex);
                    $add = 4 - $len;
                    if ($len < 4) {
                        for ($j = 0; $j < $add; $j++) {
                            $hex = "0" . $hex;
                        }
                    }
                    $hex2 .= $hex;
                }

                return $hex2;
            } else {
                return $hex1;
            }
        } else {
            return 'failed';
        }
    }


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
            if ($sending_server->custom && $sending_server->type == 'http') {
                $cg_info = $sending_server->customSendingServer;

                $send_custom_data = [];


                $username_param = $cg_info->username_param;
                $username_value = $cg_info->username_value;
                $password_value = null;

                if ($cg_info->authorization == 'no_auth') {
                    $send_custom_data[$username_param] = $username_value;
                }

                if ($cg_info->password_status) {
                    $password_param = $cg_info->password_param;
                    $password_value = $cg_info->password_value;

                    if ($cg_info->authorization == 'no_auth') {
                        $send_custom_data[$password_param] = $password_value;
                    }
                }

                if ($cg_info->action_status) {
                    $action_param = $cg_info->action_param;
                    $action_value = $cg_info->action_value;

                    $send_custom_data[$action_param] = $action_value;
                }

                if ($cg_info->source_status) {
                    $source_param = $cg_info->source_param;
                    $source_value = $cg_info->source_value;

                    if ($data['sender_id'] != '') {
                        $send_custom_data[$source_param] = $data['sender_id'];
                    } else {
                        $send_custom_data[$source_param] = $source_value;
                    }
                }

                $destination_param                    = $cg_info->destination_param;
                $send_custom_data[$destination_param] = $data['phone'];

                $message_param                    = $cg_info->message_param;
                $send_custom_data[$message_param] = $data['message'];

                if ($cg_info->unicode_status && $data['sms_type'] == 'unicode') {
                    $unicode_param                    = $cg_info->unicode_param;
                    $unicode_value                    = $cg_info->unicode_value;
                    $send_custom_data[$unicode_param] = $unicode_value;
                }

                if ($cg_info->route_status) {
                    $route_param = $cg_info->route_param;
                    $route_value = $cg_info->route_value;

                    $send_custom_data[$route_param] = $route_value;
                }

                if ($cg_info->language_status) {
                    $language_param = $cg_info->language_param;
                    $language_value = $cg_info->language_value;

                    $send_custom_data[$language_param] = $language_value;
                }

                if ($cg_info->custom_one_status) {
                    $custom_one_param = $cg_info->custom_one_param;
                    $custom_one_value = $cg_info->custom_one_value;

                    $send_custom_data[$custom_one_param] = $custom_one_value;
                }

                if ($cg_info->custom_two_status) {
                    $custom_two_param = $cg_info->custom_two_param;
                    $custom_two_value = $cg_info->custom_two_value;

                    $send_custom_data[$custom_two_param] = $custom_two_value;
                }

                if ($cg_info->custom_three_status) {
                    $custom_three_param = $cg_info->custom_three_param;
                    $custom_three_value = $cg_info->custom_three_value;

                    $send_custom_data[$custom_three_param] = $custom_three_value;
                }

                //if json encoded then encode custom data json_encode($send_custom_data) otherwise do http_build_query
                if ($cg_info->json_encoded_post) {
                    $parameters = json_encode($send_custom_data);
                } else {
                    $parameters = http_build_query($send_custom_data);
                }

                $ch = curl_init();

                //if http method get
                if ($cg_info->http_request_method == 'get') {
                    $gateway_url = $sending_server->api_link . '?' . $parameters;

                    curl_setopt($ch, CURLOPT_URL, $gateway_url);
                    curl_setopt($ch, CURLOPT_HTTPGET, 1);
                } else {

                    //if http method post
                    $gateway_url = $sending_server->api_link;

                    curl_setopt($ch, CURLOPT_URL, $gateway_url);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
                }

                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                // if ssl verify ignore set yes then add these two values in curl  curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                if ($cg_info->ssl_certificate_verification) {
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                }
                $headers = [];
                //if content type value not none then insert content type in curl headers. $headers[] = "Content-Type: application/x-www-form-urlencoded";
                if ($cg_info->content_type != 'none') {
                    $headers[] = "Content-Type: " . $cg_info->content_type;
                }

                //if content type accept value not none then insert content type accept in curl headers. $headers[] = "Accept: application/json";
                if ($cg_info->content_type_accept != 'none') {
                    $headers[] = "Accept: " . $cg_info->content_type_accept;
                }

                //if content encoding value not none then insert content type accept in curl headers. $headers[] = "charset=utf-8";
                if ($cg_info->character_encoding != 'none') {
                    $headers[] = "charset=" . $cg_info->character_encoding;
                }
                // if authorization set Bearer then add this line on curl header $header[] = "Authorization: Bearer ".$gateway_user_name;

                if ($cg_info->authorization == 'bearer_token') {
                    $headers[] = "Authorization: Bearer " . $username_value;
                }

                // if authorization set basic auth then add this line on curl header $header[] = "Authorization: Basic ".base64_encode("$gateway_user_name:$gateway_password");

                if ($cg_info->authorization == 'basic_auth') {
                    $headers[] = "Authorization: Basic " . base64_encode("$username_value:$password_value");
                }

                if (count($headers)) {
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                }

                $get_sms_status = curl_exec($ch);

                if (curl_errno($ch)) {
                    $get_sms_status = curl_error($ch);
                } else {
                    if (substr_count(strtolower($get_sms_status), strtolower($sending_server->success_keyword)) == 1) {
                        $get_sms_status = 'Delivered';
                    }
                }
                curl_close($ch);
            } else {

                $gateway_url = $sending_server->api_link;

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
     * send voice message
     *
     * @param $data
     *
     * @return array|Application|Translator|string|null
     * @throws Exception
     */
    public function sendVoiceSMS($data)
    {
        $phone          = $data['phone'];
        $sending_server = $data['sending_server'];
        $gateway_name   = $data['sending_server']->settings;
        $message        = null;
        $get_sms_status = $data['status'];
        $language       = $data['language'];
        $gender         = $data['gender'];

        if (isset($data['message'])) {
            $message = $data['message'];
        }
        if ($get_sms_status == null) {
            switch ($gateway_name) {
                case 'Twilio':

                    try {
                        $client = new Client($sending_server->account_sid, $sending_server->auth_token);

                        $response = new VoiceResponse();

                        if ($gender == 'male') {
                            $voice = 'man';
                        } else {
                            $voice = 'woman';
                        }

                        $response->say($message, ['voice' => $voice, 'language' => $language]);

                        $get_response = $client->calls->create($phone, $data['sender_id'], [
                            "twiml" => $response,
                        ]);

                        if ($get_response->status == 'queued') {
                            $get_sms_status = 'Delivered';
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
            'sms_type'          => 'voice',
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
            $gateway_url = $sending_server->api_link;
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
