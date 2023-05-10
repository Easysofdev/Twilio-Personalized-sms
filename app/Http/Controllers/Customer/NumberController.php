<?php

namespace App\Http\Controllers\Customer;

use App\Http\Requests\SenderID\PayPaymentRequest;
use App\Library\Tool;
use App\Models\PaymentMethods;
use App\Models\PhoneNumbers;
use App\Repositories\Contracts\PhoneNumberRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Twilio\Rest\Client;

class NumberController extends CustomerBaseController
{

    protected $numbers;


    /**
     * PhoneNumberController constructor.
     *
     * @param  PhoneNumberRepository  $numbers
     */

    public function __construct(PhoneNumberRepository $numbers)
    {
        $this->numbers = $numbers;
    }

    /**
     * @return Application|Factory|View
     * @throws AuthorizationException
     */
    public function index()
    {

        $this->authorize('view_numbers');

        $breadcrumbs = [
            ['link' => url('dashboard'), 'name' => __('locale.menu.Dashboard')],
            ['link' => url('dashboard'), 'name' => __('locale.menu.Sending')],
            ['name' => __('locale.menu.Numbers')],
        ];

        return view('customer.Numbers.index', compact('breadcrumbs'));
    }


    /**
     * @param  Request  $request
     *
     * @return void
     * @throws AuthorizationException
     */
    public function search(Request $request)
    {

        $this->authorize('view_numbers');

        $sid = config('services.twilio.sid');
        $token = config('services.twilio.auth_token');

        $client = new Client($sid, $token);

        $params = [
            'excludeAllAddressRequired' => true,
            'excludeLocalAddressRequired' => true,
            'excludeForeignAddressRequired' => true,
            'smsEnabled' => true,
        ];

        $search = $request->input('search.value');
        if (!empty($search)) {
            $params['contains'] = $search;
        }

        $numbers = $client->availablePhoneNumbers('US')
            ->local
            ->read($params);

        $totalData = count($numbers);
        $totalFiltered = $totalData;

        $limit = $request->input('length');
        $start = $request->input('start');

        $numbers = array_slice($numbers, $start, $limit);

        $data = [];
        if (!empty($numbers)) {
            foreach ($numbers as $number) {

                $nestedData['responsive_id'] = '';
                $nestedData['number']        = $number->friendlyName;
                $nestedData['status']        = '<span class="badge bg-success text-uppercase">' . __('locale.labels.available') . '</span>';

                $number_capabilities  = '';
                if ($number->capabilities->getMms()) {
                    $number_capabilities .= '<span class="badge bg-primary text-uppercase me-1"><i data-feather="message-square" class="me-25"></i><span>' . __('locale.labels.sms') . '</span></span>';
                }

                $nestedData['capabilities'] = $number_capabilities;

                $nestedData['buy_label']     = __('locale.labels.buy');
                $nestedData['buy']           = route('customer.numbers.search', $number->friendlyName);

                $data[]                      = $nestedData;
            }
        }

        $json_data = [
            "draw"            => intval($request->input('draw')),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data,
        ];

        echo json_encode($json_data);
        exit();
    }


    /**
     * show available numbers
     *
     * @return Application|Factory|\Illuminate\Contracts\View\View
     * @throws AuthorizationException
     */
    public function buy()
    {
        $this->authorize('buy_numbers');

        $breadcrumbs = [
            ['link' => url('dashboard'), 'name' => __('locale.menu.Dashboard')],
            ['link' => url('numbers'), 'name' => __('locale.menu.Numbers')],
            ['name' => __('locale.phone_numbers.buy_number')],
        ];

        return view('customer.Numbers.buy', compact('breadcrumbs'));
    }

    /**
     * @param  Request  $request
     *
     * @return void
     * @throws AuthorizationException
     */
    public function availableNumbers(Request $request)
    {

        $this->authorize('buy_numbers');

        $sid = config('services.twilio.sid');
        $token = config('services.twilio.auth_token');

        $client = new Client($sid, $token);

        $params = [
            'excludeAllAddressRequired' => true,
            'excludeLocalAddressRequired' => true,
            'excludeForeignAddressRequired' => true,
            'smsEnabled' => true,
        ];

        $search = $request->input('search.value');
        if (!empty($search)) {
            $params['contains'] = $search;
        }

        $numbers = $client->availablePhoneNumbers('US')
            ->local
            ->read($params);

        $totalData = count($numbers);
        $totalFiltered = $totalData;

        $data = [];
        if (!empty($numbers)) {
            foreach ($numbers as $number) {

                $nestedData['responsive_id'] = '';
                $nestedData['buy']           = __('locale.labels.buy');
                $nestedData['number']        = $number->friendlyName;

                $nestedData['checkout']      = route('customer.numbers.pay', $number->phoneNumber);

                $number_capabilities  = '';
                if ($number->capabilities->getMms()) {
                    $number_capabilities .= '<span class="badge bg-primary text-uppercase me-1"><i data-feather="message-square" class="me-25"></i><span>' . __('locale.labels.sms') . '</span></span>';
                }

                $nestedData['capabilities'] = $number_capabilities;
                $data[]                      = $nestedData;
            }
        }

        $json_data = [
            "draw"            => intval($request->input('draw')),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data,
        ];

        echo json_encode($json_data);
        exit();
    }


    /**
     * @param  PhoneNumbers  $phone_number
     * @param $id
     *
     * @return JsonResponse Controller|JsonResponse
     *
     * @throws AuthorizationException
     */
    public function release(PhoneNumbers $phone_number, $id): JsonResponse
    {
        $this->authorize('release_numbers');

        $this->numbers->release($phone_number, $id);

        return response()->json([
            'status'  => 'success',
            'message' => __('locale.phone_numbers.number_successfully_released'),
        ]);
    }

    /**
     * batch release
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function batchAction(Request $request): JsonResponse
    {
        $ids     = $request->get('ids');
        $numbers = PhoneNumbers::where('user_id', Auth::user()->id)->whereIn('uid', $ids)->cursor();

        foreach ($numbers as $number) {
            $number->user_id       = 1;
            $number->status        = 'available';
            $number->validity_date = null;

            $number->save();
        }

        return response()->json([
            'status'  => 'success',
            'message' => __('locale.phone_numbers.number_successfully_released'),
        ]);
    }


    /**
     * checkout
     *
     * @param  PhoneNumbers  $number
     *
     * @return Application|Factory|\Illuminate\Contracts\View\View
     * @throws AuthorizationException
     */
    public function pay(PhoneNumbers $number)
    {

        $this->authorize('buy_numbers');

        $pageConfigs = [
            'bodyClass' => 'ecommerce-application',
        ];

        $breadcrumbs = [
            ['link' => url('dashboard'), 'name' => __('locale.menu.Dashboard')],
            ['link' => url('dashboard'), 'name' => __('locale.menu.Sending')],
            ['link' => url('numbers'), 'name' => __('locale.menu.Numbers')],
            ['name' => __('locale.labels.checkout')],
        ];

        $payment_methods = PaymentMethods::where('status', true)->cursor();

        return view('customer.Numbers.checkout', compact('breadcrumbs', 'pageConfigs', 'number', 'payment_methods'));
    }


    /**
     * pay sender id payment
     *
     * @param  PhoneNumbers  $number
     * @param  PayPaymentRequest  $request
     *
     * @return Application|Factory|\Illuminate\Contracts\View\View|RedirectResponse
     */
    public function payment(PhoneNumbers $number, PayPaymentRequest $request)
    {

        $data = $this->numbers->payPayment($number, $request->except('_token'));

        if (isset($data)) {

            if ($data->getData()->status == 'success') {

                if ($request->payment_methods == 'stripe') {
                    return view('customer.Payments.stripe', [
                        'session_id'      => $data->getData()->session_id,
                        'publishable_key' => $data->getData()->publishable_key,
                        'number'          => $number,
                    ]);
                }

                return redirect()->to($data->getData()->redirect_url);
            }

            return redirect()->route('customer.numbers.pay', $number->uid)->with([
                'status'  => 'error',
                'message' => $data->getData()->message,
            ]);
        }

        return redirect()->route('customer.numbers.pay', $number->uid)->with([
            'status'  => 'error',
            'message' => __('locale.exceptions.something_went_wrong'),
        ]);
    }
}
