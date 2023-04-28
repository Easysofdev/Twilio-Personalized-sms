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
use App\Models\Senderid;
use App\Models\SenderidPlan;
use App\Repositories\Contracts\SenderIDRepository;
use Braintree\Gateway;
use Carbon\Carbon;
use Exception;
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

class EloquentSenderIDRepository extends EloquentBaseRepository implements SenderIDRepository
{
    /**
     * EloquentSenderIDRepository constructor.
     *
     * @param  Senderid  $senderid
     */
    public function __construct(Senderid $senderid)
    {
        parent::__construct($senderid);
    }

    /**
     * @param  array  $input
     * @param  array  $billingCycle
     *
     * @return Senderid|mixed
     *
     * @throws GeneralException
     */
    public function store(array $input, array $billingCycle): Senderid
    {
        /** @var Senderid $senderid */
        $senderid = $this->make(Arr::only($input, [
            'user_id',
            'sender_id',
            'status',
            'price',
            'billing_cycle',
            'frequency_amount',
            'frequency_unit',
            'currency_id',
        ]));

        if (isset($input['billing_cycle']) && $input['billing_cycle'] != 'custom') {
            $limits                     = $billingCycle[$input['billing_cycle']];
            $senderid->frequency_amount = $limits['frequency_amount'];
            $senderid->frequency_unit   = $limits['frequency_unit'];
        }

        if ($input['status'] == 'active') {
            $current                   = Carbon::now();
            $senderid->validity_date   = $current->add($senderid->frequency_unit, $senderid->frequency_amount);
            $senderid->payment_claimed = true;
        }


        if (!$this->save($senderid)) {
            throw new GeneralException(__('locale.exceptions.something_went_wrong'));
        }

        return $senderid;
    }

    /**
     * @param  array  $input
     *
     * @return Senderid|mixed
     *
     * @throws GeneralException
     */
    public function storeCustom(array $input): Senderid
    {
        /** @var Senderid $senderid */
        $senderid = $this->make(Arr::only($input, [
            'sender_id',
        ]));

        $plan                       = SenderidPlan::find($input['plan']);
        $senderid->user_id          = Auth::user()->id;
        $senderid->currency_id      = $plan->currency_id;
        $senderid->status           = 'Pending';
        $senderid->price            = $plan->price;
        $senderid->billing_cycle    = $plan->billing_cycle;
        $senderid->frequency_amount = $plan->frequency_amount;
        $senderid->frequency_unit   = $plan->frequency_unit;

        if (!$this->save($senderid)) {
            throw new GeneralException(__('locale.exceptions.something_went_wrong'));
        }

        return $senderid;
    }

    /**
     * @param  Senderid  $senderid
     *
     * @return bool
     */
    private function save(Senderid $senderid): bool
    {
        if (!$senderid->save()) {
            return false;
        }

        return true;
    }

    /**
     * @param  Senderid  $senderid
     * @param  array  $input
     * @param  array  $billingCycle
     *
     * @return Senderid
     * @throws GeneralException
     */
    public function update(Senderid $senderid, array $input, array $billingCycle): Senderid
    {
        if (isset($input['billing_cycle']) && $input['billing_cycle'] != 'custom') {
            $limits                    = $billingCycle[$input['billing_cycle']];
            $input['frequency_amount'] = $limits['frequency_amount'];
            $input['frequency_unit']   = $limits['frequency_unit'];
        }

        if ($senderid->status != 'active' && $input['status'] == 'active') {
            $current                  = Carbon::now();
            $input['validity_date']   = $current->add($senderid->frequency_unit, $senderid->frequency_amount);
            $input['payment_claimed'] = true;
        }

        if (!$senderid->update($input)) {
            throw new GeneralException(__('locale.exceptions.something_went_wrong'));
        }

        return $senderid;
    }

    /**
     * @param  Senderid  $senderid
     * @param  null  $user_id
     *
     * @return bool
     * @throws GeneralException
     * @throws Exception
     */
    public function destroy(Senderid $senderid, $user_id = null)
    {
        if ($user_id) {
            $exist = $senderid->where('sender_id', $senderid->sender_id)->where('user_id', $user_id)->first();
            if ($exist) {
                if (!$exist->delete()) {
                    throw new GeneralException(__('locale.exceptions.something_went_wrong'));
                }

                return true;
            }
            throw new GeneralException(__('locale.exceptions.something_went_wrong'));
        } else {
            if (!$senderid->delete()) {
                throw new GeneralException(__('locale.exceptions.something_went_wrong'));
            }
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
    public function batchActive(array $ids): bool
    {
        DB::transaction(function () use ($ids) {
            if ($this->query()->whereIn('uid', $ids)
                ->update(['status' => 'active'])
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
    public function batchBlock(array $ids): bool
    {
        DB::transaction(function () use ($ids) {
            if ($this->query()->whereIn('uid', $ids)
                ->update(['status' => 'block'])
            ) {
                return true;
            }

            throw new GeneralException(__('locale.exceptions.something_went_wrong'));
        });

        return true;
    }

    /**
     * store sender id plan
     *
     * @param  array  $input
     * @param  array  $billingCycle
     *
     * @return mixed
     * @throws GeneralException
     */
    public function storePlan(array $input, array $billingCycle)
    {
        if (isset($input['billing_cycle']) && $input['billing_cycle'] != 'custom') {
            $limits                    = $billingCycle[$input['billing_cycle']];
            $input['frequency_amount'] = $limits['frequency_amount'];
            $input['frequency_unit']   = $limits['frequency_unit'];
        }

        $sender_id_plan = SenderidPlan::create($input);
        if (!$sender_id_plan) {
            throw new GeneralException(__('locale.exceptions.something_went_wrong'));
        }

        return $sender_id_plan;
    }


    /**
     * pay the payment
     *
     * @param  Senderid  $senderid
     * @param  array  $input
     *
     * @return JsonResponse
     */
    public function payPayment(Senderid $senderid, array $input): JsonResponse
    {

        $paymentMethod = PaymentMethods::where('status', true)->where('type', $input['payment_methods'])->first();

        if ($paymentMethod) {
            $credentials = json_decode($paymentMethod->options);


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
                                    'currency'     => $senderid->currency->code,
                                    'unit_amount'  => $senderid->price * 100,
                                    'product_data' => [
                                        'name' => __('locale.sender_id.payment_for_sender_id') . ' ' . $senderid->sender_id,
                                    ],
                                ],
                                'quantity'   => 1,
                            ]],
                            'mode'                 => 'payment',
                            'success_url'          => route('customer.senderid.payment_success', $senderid->uid),
                            'cancel_url'           => route('customer.senderid.payment_cancel', $senderid->uid),
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
