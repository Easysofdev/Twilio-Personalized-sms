<?php

namespace App\Http\Requests\SendingServer;

use Illuminate\Foundation\Http\FormRequest;

class StoreSendingServerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $type = $this->input('settings');

        $rules = [
                'name'            => 'required',
                'settings'        => 'required',
                'quota_value'     => 'required|numeric',
                'quota_base'      => 'required|numeric',
                'quota_unit'      => 'required',
                'sms_per_request' => 'required|numeric',
        ];

        switch ($type) {
            case 'Twilio':
            case 'TwilioCopilot':
                $rules['account_sid'] = 'required';
                $rules['auth_token']  = 'required';
                break;
        }

        return $rules;
    }

}
