<?php

namespace App\Repositories\Eloquent;

use App\Exceptions\GeneralException;
use App\Library\aamarPay;
use App\Library\CoinPayments;
use App\Library\Flutterwave;
use App\Library\PayU;
use App\Library\PayUMoney;
use App\Library\TwoCheckout;
use App\Models\Keywords;
use App\Models\PaymentMethods;
use App\Models\User;
use App\Repositories\Contracts\KeywordRepository;
use Braintree\Gateway;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Paynow\Http\ConnectionException;
use Paynow\Payments\HashMismatchException;
use Paynow\Payments\InvalidIntegrationException;
use Paynow\Payments\Paynow;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\BadRequestError;
use Session;
use Stripe\Stripe;
use Throwable;

class EloquentKeywordRepository extends EloquentBaseRepository implements KeywordRepository
{
    /**
     * EloquentKeywordRepository constructor.
     *
     * @param  Keywords  $keyword
     */
    public function __construct(Keywords $keyword)
    {
        parent::__construct($keyword);
    }

    /**
     * @param  array  $input
     *
     * @param  array  $billingCycle
     *
     * @return Keywords|mixed
     *
     * @throws GeneralException
     */
    public function store(array $input, array $billingCycle): Keywords
    {

        /** @var Keywords $keyword */
        $keyword = $this->make(Arr::only($input, [
            'title',
            'sender_id',
            'keyword_name',
            'reply_text',
            'reply_voice',
            'price',
            'billing_cycle',
            'frequency_amount',
            'frequency_unit',
            'currency_id',
            'status',
        ]));

        $media_url = null;

        if (isset($input['reply_mms'])) {
            $image      = $input['reply_mms'];
            $media_path = $image->store('mms_file', 'public');
            $media_url  = asset(Storage::url($media_path));
        }

        $keyword->reply_mms = $media_url;

        if (isset($input['billing_cycle']) && $input['billing_cycle'] != 'custom') {
            $limits                    = $billingCycle[$input['billing_cycle']];
            $keyword->frequency_amount = $limits['frequency_amount'];
            $keyword->frequency_unit   = $limits['frequency_unit'];
        }


        if ($input['user_id'] != 0) {
            $user = User::find($input['user_id'])->is_customer;
            if ($user) {
                $input['status']  = 'assigned';
                $keyword->status  = 'assigned';
                $keyword->user_id = $input['user_id'];
            } else {
                throw new GeneralException(__('locale.auth.user_not_exist'));
            }
        } else {
            $keyword->user_id = 1;
        }

        if ($input['status'] == 'assigned') {
            $current                = Carbon::now();
            $keyword->validity_date = $current->add($keyword->frequency_unit, $keyword->frequency_amount);
        }

        if (!$this->save($keyword)) {
            throw new GeneralException(__('locale.exceptions.something_went_wrong'));
        }

        return $keyword;
    }

    /**
     * @param  Keywords  $keyword
     *
     * @return bool
     */
    private function save(Keywords $keyword): bool
    {
        if (!$keyword->save()) {
            return false;
        }

        return true;
    }

    /**
     * @param  Keywords  $keyword
     * @param  array  $input
     *
     * @param  array  $billingCycle
     *
     * @return Keywords
     * @throws GeneralException
     */
    public function update(Keywords $keyword, array $input, array $billingCycle): Keywords
    {
        if (isset($input['reply_mms'])) {
            $image      = $input['reply_mms'];
            $media_path = $image->store('mms_file', 'public');
            $media_url  = asset(Storage::url($media_path));
        } else {
            $media_url = $keyword->reply_mms;
        }

        $input['reply_mms'] = $media_url;

        if (isset($input['billing_cycle']) && $input['billing_cycle'] != 'custom') {
            $limits                    = $billingCycle[$input['billing_cycle']];
            $input['frequency_amount'] = $limits['frequency_amount'];
            $input['frequency_unit']   = $limits['frequency_unit'];
        }

        if (!$keyword->update($input)) {
            throw new GeneralException(__('locale.exceptions.something_went_wrong'));
        }

        return $keyword;
    }

    /**
     * @param  Keywords  $keyword
     *
     * @return bool|null
     * @throws Exception|Throwable
     *
     */
    public function destroy(Keywords $keyword)
    {
        if (!$keyword->delete()) {
            throw new GeneralException(__('locale.exceptions.something_went_wrong'));
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
    public function batchDestroy(array $ids): bool
    {
        DB::transaction(function () use ($ids) {
            // This won't call eloquent events, change to destroy if needed
            if ($this->query()->whereIn('uid', $ids)->delete()) {
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
    public function batchAvailable(array $ids): bool
    {
        DB::transaction(function () use ($ids) {
            if ($this->query()->whereIn('uid', $ids)
                ->update(['status' => 'available'])
            ) {
                return true;
            }

            throw new GeneralException(__('locale.exceptions.something_went_wrong'));
        });

        return true;
    }

    /**
     * update keyword information by customer
     *
     * @param  Keywords  $keyword
     * @param  array  $input
     *
     * @return Keywords
     * @throws GeneralException
     */
    public function updateByCustomer(Keywords $keyword, array $input): Keywords
    {
        if (isset($input['originator'])) {
            if ($input['originator'] == 'sender_id') {
                $sender_id = $input['sender_id'];
            } else {
                $sender_id = $input['phone_number'];
            }
            $input['sender_id'] = $sender_id;
        }

        if (isset($input['reply_mms'])) {
            $image      = $input['reply_mms'];
            $media_path = $image->store('mms_file', 'public');
            $media_url  = asset(Storage::url($media_path));
        } else {
            $media_url = $keyword->reply_mms;
        }

        $input['reply_mms'] = $media_url;

        unset($input['originator']);
        unset($input['phone_number']);

        if (!$keyword->update($input)) {
            throw new GeneralException(__('locale.exceptions.something_went_wrong'));
        }

        return $keyword;
    }


    /**
     * release number
     *
     * @param  Keywords  $keyword
     * @param  string  $id
     *
     * @return bool
     * @throws GeneralException
     */
    public function release(Keywords $keyword, string $id): bool
    {
        $available = $keyword->where('user_id', Auth::user()->id)->where('uid', $id)->first();

        if ($available) {
            $available->user_id       = 1;
            $available->status        = 'available';
            $available->validity_date = null;
            if (!$available->save()) {
                throw new GeneralException(__('locale.exceptions.something_went_wrong'));
            }

            return true;
        }

        throw new GeneralException(__('locale.exceptions.something_went_wrong'));
    }


    /**
     * pay the payment
     *
     * @param  Keywords  $keyword
     * @param  array  $input
     *
     * @return JsonResponse
     */
    public function payPayment(Keywords $keyword, array $input): JsonResponse
    {

        $paymentMethod = PaymentMethods::where('status', true)->where('type', $input['payment_methods'])->first();

        if ($paymentMethod) {
            $credentials = json_decode($paymentMethod->options);

            $item_name = __('locale.keywords.payment_for_keyword') . ' ' . $keyword->keyword_name;

            switch ($paymentMethod->type) {

                case 'stripe':

                    $publishable_key = $credentials->publishable_key;
                    $secret_key      = $credentials->secret_key;

                    Stripe::setApiKey($secret_key);

                    try {
                        $checkout_session = \Stripe\Checkout\Session::create([
                            'payment_method_types' => ['card'],
                            'customer_email'       => $input['email'],
                            'line_items'           => [[
                                'price_data' => [
                                    'currency'     => $keyword->currency->code,
                                    'unit_amount'  => $keyword->price * 100,
                                    'product_data' => [
                                        'name' => $item_name,
                                    ],
                                ],
                                'quantity'   => 1,
                            ]],
                            'mode'                 => 'payment',
                            'success_url'          => route('customer.keywords.payment_success', $keyword->uid),
                            'cancel_url'           => route('customer.keywords.payment_cancel', $keyword->uid),
                        ]);

                        if (!empty($checkout_session->id)) {
                            Session::put('payment_method', $paymentMethod->type);
                            Session::put('session_id', $checkout_session->id);
                        }

                        return response()->json([
                            'status'          => 'success',
                            'session_id'      => $checkout_session->id,
                            'publishable_key' => $publishable_key,
                        ]);
                    } catch (Exception $exception) {

                        return response()->json([
                            'status'  => 'error',
                            'message' => $exception->getMessage(),
                        ]);
                    }
            }

            return response()->json([
                'status'  => 'error',
                'message' => __('locale.payment_gateways.not_found'),
            ]);
        }

        return response()->json([
            'status'  => 'error',
            'message' => __('locale.payment_gateways.not_found'),
        ]);
    }
}
