<?php

namespace App\Repositories\Eloquent;

use App\Exceptions\GeneralException;
use App\Library\aamarPay;
use App\Library\CoinPayments;
use App\Library\Flutterwave;
use App\Library\PayU;
use App\Library\PayUMoney;
use App\Library\TwoCheckout;
use App\Models\PaymentMethods;
use App\Models\PhoneNumbers;
use App\Models\User;
use App\Repositories\Contracts\PhoneNumberRepository;
use Braintree\Gateway;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

class EloquentPhoneNumberRepository extends EloquentBaseRepository implements PhoneNumberRepository
{
    /**
     * EloquentPhoneNumberRepository constructor.
     *
     * @param  PhoneNumbers  $number
     */
    public function __construct(PhoneNumbers $number)
    {
        parent::__construct($number);
    }

    /**
     * @param  array  $input
     * @param  array  $billingCycle
     *
     * @return PhoneNumbers|mixed
     *
     * @throws GeneralException
     */
    public function store(array $input, array $billingCycle): PhoneNumbers
    {
        /** @var PhoneNumbers $number */
        $number = $this->make(Arr::only($input, [
            'user_id',
            'status',
            'price',
            'billing_cycle',
            'frequency_amount',
            'frequency_unit',
            'currency_id',
        ]));

        $number->number       = str_replace(['(', ')', '+', '-', ' '], '', $input['number']);
        $number->capabilities = json_encode($input['capabilities']);

        if (isset($input['billing_cycle']) && $input['billing_cycle'] != 'custom') {
            $limits                   = $billingCycle[$input['billing_cycle']];
            $number->frequency_amount = $limits['frequency_amount'];
            $number->frequency_unit   = $limits['frequency_unit'];
        }

        $user = User::find($input['user_id'])->is_customer;
        if ($user) {
            $number->status = 'assigned';
        }

        if (!$this->save($number)) {
            throw new GeneralException(__('locale.exceptions.something_went_wrong'));
        }

        return $number;
    }

    /**
     * @param  PhoneNumbers  $number
     *
     * @return bool
     */
    private function save(PhoneNumbers $number): bool
    {
        if (!$number->save()) {
            return false;
        }

        return true;
    }

    /**
     * @param  PhoneNumbers  $number
     * @param  array  $input
     * @param  array  $billingCycle
     *
     * @return PhoneNumbers
     * @throws GeneralException
     */
    public function update(PhoneNumbers $number, array $input, array $billingCycle): PhoneNumbers
    {

        $input['number']       = str_replace(['(', ')', '+', '-', ' '], '', $input['number']);
        $input['capabilities'] = json_encode($input['capabilities']);
        if (isset($input['billing_cycle']) && $input['billing_cycle'] != 'custom') {
            $limits                    = $billingCycle[$input['billing_cycle']];
            $input['frequency_amount'] = $limits['frequency_amount'];
            $input['frequency_unit']   = $limits['frequency_unit'];
        }

        if (!$number->update($input)) {
            throw new GeneralException(__('locale.exceptions.something_went_wrong'));
        }

        return $number;
    }

    /**
     * @param  PhoneNumbers  $number
     *
     * @return bool|null
     * @throws Exception|Throwable
     *
     */
    public function destroy(PhoneNumbers $number)
    {
        if (!$number->delete()) {
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
            // This wont call eloquent events, change to destroy if needed
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
     * release number
     *
     * @param  PhoneNumbers  $number
     * @param  string  $id
     *
     * @return bool
     * @throws GeneralException
     */
    public function release(PhoneNumbers $number, string $id): bool
    {
        $available = $number->where('user_id', Auth::user()->id)->where('uid', $id)->first();

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
     * @param  PhoneNumbers  $number
     * @param  array  $input
     *
     * @return JsonResponse
     */
    public function payPayment(PhoneNumbers $number, array $input): JsonResponse
    {

        $paymentMethod = PaymentMethods::where('status', true)->where('type', $input['payment_methods'])->first();

        if ($paymentMethod) {
            $credentials = json_decode($paymentMethod->options);

            $item_name = __('locale.phone_numbers.payment_for_number') . ' ' . $number->number;

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
                                    'currency'     => $number->currency->code,
                                    'unit_amount'  => $number->price * 100,
                                    'product_data' => [
                                        'name' => $item_name,
                                    ],
                                ],
                                'quantity'   => 1,
                            ]],
                            'mode'                 => 'payment',
                            'success_url'          => route('customer.numbers.payment_success', $number->uid),
                            'cancel_url'           => route('customer.numbers.payment_cancel', $number->uid),
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
