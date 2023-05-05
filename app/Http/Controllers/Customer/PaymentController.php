<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Invoices;
use App\Models\Keywords;
use App\Models\Notifications;
use App\Models\PaymentMethods;
use App\Models\PhoneNumbers;
use App\Models\Plan;
use App\Models\Senderid;
use App\Models\Subscription;
use App\Models\SubscriptionLog;
use App\Models\SubscriptionTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class PaymentController extends Controller
{

    public function createNotification($type = null, $name = null, $user_name = null)
    {
        $user = User::first();

        Notifications::create([
            'user_id'           => $user->id,
            'notification_for'  => 'admin',
            'notification_type' => $type,
            'message'           => $name . ' Purchased By ' . $user_name,
        ]);
    }

    /**
     * successful sender id purchase
     *
     * @param  Senderid  $senderid
     * @param  Request  $request
     *
     * @return RedirectResponse
     */
    public function successfulSenderIDPayment(Senderid $senderid, Request $request): RedirectResponse
    {
        $payment_method = Session::get('payment_method');

        switch ($payment_method) {

            case 'stripe':
                $paymentMethod = PaymentMethods::where('status', true)->where('type', 'stripe')->first();
                if ($payment_method) {
                    $credentials = json_decode($paymentMethod->options);
                    $secret_key  = $credentials->secret_key;
                    $session_id  = Session::get('session_id');

                    $stripe = new StripeClient($secret_key);

                    try {
                        $response = $stripe->checkout->sessions->retrieve($session_id);

                        if ($response->payment_status == 'paid') {

                            $invoice = Invoices::create([
                                'user_id'        => $senderid->user_id,
                                'currency_id'    => $senderid->currency_id,
                                'payment_method' => $paymentMethod->id,
                                'amount'         => $senderid->price,
                                'type'           => Invoices::TYPE_SENDERID,
                                'description'    => __('locale.sender_id.payment_for_sender_id') . ' ' . $senderid->sender_id,
                                'transaction_id' => $response->payment_intent,
                                'status'         => Invoices::STATUS_PAID,
                            ]);

                            if ($invoice) {
                                $current                   = Carbon::now();
                                $senderid->validity_date   = $current->add($senderid->frequency_unit, $senderid->frequency_amount);
                                $senderid->status          = 'active';
                                $senderid->payment_claimed = true;
                                $senderid->save();

                                $this->createNotification('senderid', $senderid->sender_id, User::find($senderid->user_id)->displayName());

                                return redirect()->route('customer.senderid.index')->with([
                                    'status'  => 'success',
                                    'message' => __('locale.payment_gateways.payment_successfully_made'),
                                ]);
                            }

                            return redirect()->route('customer.senderid.pay', $senderid->uid)->with([
                                'status'  => 'error',
                                'message' => __('locale.exceptions.something_went_wrong'),
                            ]);
                        }
                    } catch (ApiErrorException $e) {
                        return redirect()->route('customer.senderid.pay', $senderid->uid)->with([
                            'status'  => 'error',
                            'message' => $e->getMessage(),
                        ]);
                    }
                }

                return redirect()->route('customer.senderid.pay', $senderid->uid)->with([
                    'status'  => 'error',
                    'message' => __('locale.payment_gateways.not_found'),
                ]);
        }

        return redirect()->route('customer.senderid.pay', $senderid->uid)->with([
            'status'  => 'error',
            'message' => __('locale.payment_gateways.not_found'),
        ]);
    }

    /**
     * successful Top up payment
     *
     * @param  Request  $request
     *
     * @return RedirectResponse
     */
    public function successfulTopUpPayment(Request $request): RedirectResponse
    {
        $payment_method = Session::get('payment_method');
        $user           = User::find($request->user_id);

        if ($user) {
            switch ($payment_method) {

                case 'stripe':
                    $paymentMethod = PaymentMethods::where('status', true)->where('type', 'stripe')->first();
                    if ($payment_method) {
                        $credentials = json_decode($paymentMethod->options);
                        $secret_key  = $credentials->secret_key;
                        $session_id  = Session::get('session_id');

                        $stripe = new StripeClient($secret_key);

                        try {
                            $response = $stripe->checkout->sessions->retrieve($session_id);

                            $price = $response->amount_total / 100;

                            if ($response->payment_status == 'paid') {

                                $invoice = Invoices::create([
                                    'user_id'        => $user->id,
                                    'currency_id'    => $user->customer->subscription->plan->currency->id,
                                    'payment_method' => $paymentMethod->id,
                                    'amount'         => $price,
                                    'type'           => Invoices::TYPE_SUBSCRIPTION,
                                    'description'    => 'Payment for sms unit',
                                    'transaction_id' => $response->id,
                                    'status'         => Invoices::STATUS_PAID,
                                ]);

                                if ($invoice) {

                                    if ($user->sms_unit != '-1') {
                                        $user->sms_unit += $request->sms_unit;
                                        $user->save();
                                    }

                                    $subscription = $user->customer->activeSubscription();

                                    $subscription->addTransaction(SubscriptionTransaction::TYPE_SUBSCRIBE, [
                                        'status'                 => SubscriptionTransaction::STATUS_SUCCESS,
                                        'title'                  => 'Add ' . $request->sms_unit . ' sms units',
                                        'amount'                 => $request->sms_unit . ' sms units',
                                    ]);

                                    return redirect()->route('user.home')->with([
                                        'status'  => 'success',
                                        'message' => __('locale.payment_gateways.payment_successfully_made'),
                                    ]);
                                }

                                return redirect()->route('user.home')->with([
                                    'status'  => 'error',
                                    'message' => __('locale.exceptions.something_went_wrong'),
                                ]);
                            }
                        } catch (ApiErrorException $e) {
                            return redirect()->route('user.home')->with([
                                'status'  => 'error',
                                'message' => $e->getMessage(),
                            ]);
                        }
                    }

                    return redirect()->route('user.home')->with([
                        'status'  => 'error',
                        'message' => __('locale.payment_gateways.not_found'),
                    ]);
            }

            return redirect()->route('user.home')->with([
                'status'  => 'error',
                'message' => __('locale.payment_gateways.not_found'),
            ]);
        }

        return redirect()->route('user.home')->with([
            'status'  => 'error',
            'message' => __('locale.auth.user_not_exist'),
        ]);
    }


    /**
     * cancel payment
     *
     * @param  Senderid  $senderid
     * @param  Request  $request
     *
     * @return RedirectResponse
     */
    public function cancelledSenderIDPayment(Senderid $senderid, Request $request): RedirectResponse
    {

        $payment_method = Session::get('payment_method');

        switch ($payment_method) {
            case 'stripe':
                return redirect()->route('customer.senderid.pay', $senderid->uid)->with([
                    'status'  => 'info',
                    'message' => __('locale.sender_id.payment_cancelled'),
                ]);
        }


        return redirect()->route('customer.senderid.pay', $senderid->uid)->with([
            'status'  => 'info',
            'message' => __('locale.sender_id.payment_cancelled'),
        ]);
    }

    /**
     * cancel payment
     *
     * @param  Request  $request
     *
     * @return RedirectResponse
     */
    public function cancelledTopUpPayment(Request $request): RedirectResponse
    {

        $payment_method = Session::get('payment_method');

        switch ($payment_method) {
            case 'stripe':
                return redirect()->route('user.home')->with([
                    'status'  => 'info',
                    'message' => __('locale.sender_id.payment_cancelled'),
                ]);
        }

        return redirect()->route('user.home')->with([
            'status'  => 'info',
            'message' => __('locale.sender_id.payment_cancelled'),
        ]);
    }


    /**
     * cancel payment
     *
     * @param  PhoneNumbers  $number
     * @param  Request  $request
     *
     * @return RedirectResponse
     */
    public function cancelledNumberPayment(PhoneNumbers $number, Request $request): RedirectResponse
    {

        $payment_method = Session::get('payment_method');

        switch ($payment_method) {
            case 'stripe':
                return redirect()->route('customer.numbers.pay', $number->uid)->with([
                    'status'  => 'info',
                    'message' => __('locale.sender_id.payment_cancelled'),
                ]);
        }


        return redirect()->route('customer.numbers.pay', $number->uid)->with([
            'status'  => 'info',
            'message' => __('locale.sender_id.payment_cancelled'),
        ]);
    }

    /**
     * successful number purchase
     *
     * @param  PhoneNumbers  $number
     * @param  Request  $request
     *
     * @return RedirectResponse
     */
    public function successfulNumberPayment(PhoneNumbers $number, Request $request): RedirectResponse
    {
        $payment_method = Session::get('payment_method');

        switch ($payment_method) {
            case 'stripe':
                $paymentMethod = PaymentMethods::where('status', true)->where('type', 'stripe')->first();
                if ($payment_method) {
                    $credentials = json_decode($paymentMethod->options);
                    $secret_key  = $credentials->secret_key;
                    $session_id  = Session::get('session_id');

                    $stripe = new StripeClient($secret_key);

                    try {
                        $response = $stripe->checkout->sessions->retrieve($session_id);

                        if ($response->payment_status == 'paid') {
                            $invoice = Invoices::create([
                                'user_id'        => auth()->user()->id,
                                'currency_id'    => $number->currency_id,
                                'payment_method' => $paymentMethod->id,
                                'amount'         => $number->price,
                                'type'           => Invoices::TYPE_NUMBERS,
                                'description'    => __('locale.phone_numbers.payment_for_number') . ' ' . $number->number,
                                'transaction_id' => $response->payment_intent,
                                'status'         => Invoices::STATUS_PAID,
                            ]);

                            if ($invoice) {
                                $current               = Carbon::now();
                                $number->user_id       = auth()->user()->id;
                                $number->validity_date = $current->add($number->frequency_unit, $number->frequency_amount);
                                $number->status        = 'assigned';
                                $number->save();

                                $this->createNotification('number', $number->number, User::find($number->user_id)->displayName());

                                return redirect()->route('customer.numbers.index')->with([
                                    'status'  => 'success',
                                    'message' => __('locale.payment_gateways.payment_successfully_made'),
                                ]);
                            }

                            return redirect()->route('customer.numbers.pay', $number->uid)->with([
                                'status'  => 'error',
                                'message' => __('locale.exceptions.something_went_wrong'),
                            ]);
                        }
                    } catch (ApiErrorException $e) {
                        return redirect()->route('customer.numbers.pay', $number->uid)->with([
                            'status'  => 'error',
                            'message' => $e->getMessage(),
                        ]);
                    }
                }

                return redirect()->route('customer.numbers.pay', $number->uid)->with([
                    'status'  => 'error',
                    'message' => __('locale.payment_gateways.not_found'),
                ]);
        }

        return redirect()->route('customer.number.pay', $number->uid)->with([
            'status'  => 'error',
            'message' => __('locale.payment_gateways.not_found'),
        ]);
    }

    /**
     * successful keyword purchase
     *
     * @param  Keywords  $keyword
     * @param  Request  $request
     *
     * @return RedirectResponse
     */
    public function successfulKeywordPayment(Keywords $keyword, Request $request): RedirectResponse
    {
        $payment_method = Session::get('payment_method');
        switch ($payment_method) {
            case 'stripe':
                $paymentMethod = PaymentMethods::where('status', true)->where('type', 'stripe')->first();
                if ($payment_method) {
                    $credentials = json_decode($paymentMethod->options);
                    $secret_key  = $credentials->secret_key;
                    $session_id  = Session::get('session_id');

                    $stripe = new StripeClient($secret_key);

                    try {
                        $response = $stripe->checkout->sessions->retrieve($session_id);

                        if ($response->payment_status == 'paid') {
                            $invoice = Invoices::create([
                                'user_id'        => auth()->user()->id,
                                'currency_id'    => $keyword->currency_id,
                                'payment_method' => $paymentMethod->id,
                                'amount'         => $keyword->price,
                                'type'           => Invoices::TYPE_KEYWORD,
                                'description'    => __('locale.keywords.payment_for_keyword') . ' ' . $keyword->keyword_name,
                                'transaction_id' => $response->payment_intent,
                                'status'         => Invoices::STATUS_PAID,
                            ]);

                            if ($invoice) {
                                $current                = Carbon::now();
                                $keyword->user_id       = auth()->user()->id;
                                $keyword->validity_date = $current->add($keyword->frequency_unit, $keyword->frequency_amount);
                                $keyword->status        = 'assigned';
                                $keyword->save();

                                $this->createNotification('keyword', $keyword->keyword_name, User::find($keyword->user_id)->displayName());

                                return redirect()->route('customer.keywords.index')->with([
                                    'status'  => 'success',
                                    'message' => __('locale.payment_gateways.payment_successfully_made'),
                                ]);
                            }

                            return redirect()->route('customer.keywords.pay', $keyword->uid)->with([
                                'status'  => 'error',
                                'message' => __('locale.exceptions.something_went_wrong'),
                            ]);
                        }
                    } catch (ApiErrorException $e) {
                        return redirect()->route('customer.keywords.pay', $keyword->uid)->with([
                            'status'  => 'error',
                            'message' => $e->getMessage(),
                        ]);
                    }
                }

                return redirect()->route('customer.keywords.pay', $keyword->uid)->with([
                    'status'  => 'error',
                    'message' => __('locale.payment_gateways.not_found'),
                ]);
        }

        return redirect()->route('customer.keywords.pay', $keyword->uid)->with([
            'status'  => 'error',
            'message' => __('locale.payment_gateways.not_found'),
        ]);
    }

    /**
     * successful subscription purchase
     *
     * @param  Plan  $plan
     * @param  Request  $request
     *
     * @return RedirectResponse
     */
    public function successfulSubscriptionPayment(Plan $plan, Request $request): RedirectResponse
    {
        $payment_method = Session::get('payment_method');

        switch ($payment_method) {
            case 'stripe':
                $paymentMethod = PaymentMethods::where('status', true)->where('type', 'stripe')->first();
                if ($payment_method) {
                    $credentials = json_decode($paymentMethod->options);
                    $secret_key  = $credentials->secret_key;
                    $session_id  = Session::get('session_id');

                    $stripe = new StripeClient($secret_key);

                    try {
                        $response = $stripe->checkout->sessions->retrieve($session_id);

                        if ($response->payment_status == 'paid') {
                            $invoice = Invoices::create([
                                'user_id'        => auth()->user()->id,
                                'currency_id'    => $plan->currency_id,
                                'payment_method' => $paymentMethod->id,
                                'amount'         => $plan->price,
                                'type'           => Invoices::TYPE_SUBSCRIPTION,
                                'description'    => __('locale.subscription.payment_for_plan') . ' ' . $plan->name,
                                'transaction_id' => $response->payment_intent,
                                'status'         => Invoices::STATUS_PAID,
                            ]);

                            if ($invoice) {
                                if (Auth::user()->customer->activeSubscription()) {
                                    Auth::user()->customer->activeSubscription()->cancelNow();
                                }

                                if (Auth::user()->customer->subscription) {
                                    $subscription = Auth::user()->customer->subscription;
                                } else {
                                    $subscription           = new Subscription();
                                    $subscription->user_id  = Auth::user()->id;
                                    $subscription->start_at = Carbon::now();
                                }

                                $subscription->status                 = Subscription::STATUS_ACTIVE;
                                $subscription->plan_id                = $plan->getBillableId();
                                $subscription->end_period_last_days   = '10';
                                $subscription->current_period_ends_at = $subscription->getPeriodEndsAt(Carbon::now());
                                $subscription->end_at                 = null;
                                $subscription->end_by                 = null;
                                $subscription->payment_method_id      = $paymentMethod->id;
                                $subscription->save();

                                // add transaction
                                $subscription->addTransaction(SubscriptionTransaction::TYPE_SUBSCRIBE, [
                                    'end_at'                 => $subscription->end_at,
                                    'current_period_ends_at' => $subscription->current_period_ends_at,
                                    'status'                 => SubscriptionTransaction::STATUS_SUCCESS,
                                    'title'                  => trans('locale.subscription.subscribed_to_plan', ['plan' => $subscription->plan->getBillableName()]),
                                    'amount'                 => $subscription->plan->getBillableFormattedPrice(),
                                ]);

                                // add log
                                $subscription->addLog(SubscriptionLog::TYPE_ADMIN_PLAN_ASSIGNED, [
                                    'plan'  => $subscription->plan->getBillableName(),
                                    'price' => $subscription->plan->getBillableFormattedPrice(),
                                ]);


                                $user = User::find(auth()->user()->id);

                                if ($user->sms_unit == null || $user->sms_unit == '-1' || $plan->getOption('sms_max') == '-1') {
                                    $user->sms_unit = $plan->getOption('sms_max');
                                } else {
                                    if ($plan->getOption('add_previous_balance') == 'yes') {
                                        $user->sms_unit += $plan->getOption('sms_max');
                                    } else {
                                        $user->sms_unit = $plan->getOption('sms_max');
                                    }
                                }

                                $user->save();

                                $this->createNotification('plan', $plan->name, auth()->user()->displayName());


                                return redirect()->route('customer.subscriptions.index')->with([
                                    'status'  => 'success',
                                    'message' => __('locale.payment_gateways.payment_successfully_made'),
                                ]);
                            }

                            return redirect()->route('customer.subscriptions.purchase', $plan->uid)->with([
                                'status'  => 'error',
                                'message' => __('locale.exceptions.something_went_wrong'),
                            ]);
                        }
                    } catch (ApiErrorException $e) {
                        return redirect()->route('customer.subscriptions.purchase', $plan->uid)->with([
                            'status'  => 'error',
                            'message' => $e->getMessage(),
                        ]);
                    }
                }

                return redirect()->route('customer.subscriptions.purchase', $plan->uid)->with([
                    'status'  => 'error',
                    'message' => __('locale.payment_gateways.not_found'),
                ]);
        }

        return redirect()->route('customer.subscriptions.purchase', $plan->uid)->with([
            'status'  => 'error',
            'message' => __('locale.payment_gateways.not_found'),
        ]);
    }


    /**
     * cancel payment
     *
     * @param  Plan  $plan
     * @param  Request  $request
     *
     * @return RedirectResponse
     */
    public function cancelledSubscriptionPayment(Plan $plan, Request $request): RedirectResponse
    {

        $payment_method = Session::get('payment_method');

        switch ($payment_method) {
            case 'stripe':
                return redirect()->route('customer.subscriptions.purchase', $plan->uid)->with([
                    'status'  => 'info',
                    'message' => __('locale.sender_id.payment_cancelled'),
                ]);
        }


        return redirect()->route('customer.subscriptions.purchase', $plan->uid)->with([
            'status'  => 'info',
            'message' => __('locale.sender_id.payment_cancelled'),
        ]);
    }
}
