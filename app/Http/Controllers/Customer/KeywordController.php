<?php

namespace App\Http\Controllers\Customer;

use App\Http\Requests\Keywords\CustomerUpdate;
use App\Http\Requests\SenderID\PayPaymentRequest;
use App\Library\Tool;
use App\Models\Keywords;
use App\Models\PaymentMethods;
use App\Models\PhoneNumbers;
use App\Models\Senderid;
use App\Repositories\Contracts\KeywordRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class KeywordController extends CustomerBaseController
{

    protected $keywords;


    /**
     * KeywordController constructor.
     *
     * @param  KeywordRepository  $keywords
     */

    public function __construct(KeywordRepository $keywords)
    {
        $this->keywords = $keywords;
    }

    /**
     * @return Application|Factory|View
     * @throws AuthorizationException
     */

    public function index()
    {

        $this->authorize('view_keywords');

        $breadcrumbs = [
                ['link' => url('dashboard'), 'name' => __('locale.menu.Dashboard')],
                ['link' => url('dashboard'), 'name' => __('locale.menu.Sending')],
                ['name' => __('locale.menu.Keywords')],
        ];


        return view('customer.keywords.index', compact('breadcrumbs'));
    }


    /**
     * @param  Request  $request
     *
     * @return void
     * @throws AuthorizationException
     */
    public function search(Request $request)
    {

        $this->authorize('view_keywords');

        $columns = [
                0 => 'responsive_id',
                1 => 'uid',
                2 => 'uid',
                3 => 'title',
                4 => 'keyword_name',
                5 => 'price',
                6 => 'status',
                7 => 'actions',
        ];

        $totalData = Keywords::where('user_id', Auth::user()->id)->count();

        $totalFiltered = $totalData;

        $limit = $request->input('length');
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')];
        $dir   = $request->input('order.0.dir');

        if (empty($request->input('search.value'))) {
            $keywords = Keywords::where('user_id', Auth::user()->id)->offset($start)
                    ->limit($limit)
                    ->orderBy($order, $dir)
                    ->get();
        } else {
            $search = $request->input('search.value');

            $keywords = Keywords::where('user_id', Auth::user()->id)->whereLike(['uid', 'title', 'keyword_name', 'price'], $search)
                    ->offset($start)
                    ->limit($limit)
                    ->orderBy($order, $dir)
                    ->get();

            $totalFiltered = Keywords::where('user_id', Auth::user()->id)->whereLike(['uid', 'title', 'keyword_name', 'price'], $search)->count();
        }

        $data = [];
        if ( ! empty($keywords)) {
            foreach ($keywords as $keyword) {

                $is_assigned = false;
                if ($keyword->status == 'assigned') {
                    $is_assigned = true;
                    $status      = '<span class="badge bg-success text-uppercase">'.__('locale.labels.assigned').'</span>';
                } else {
                    $status = '<span class="badge bg-danger text-uppercase">'.__('locale.labels.expired').'</span>';
                }

                $reply_mms = false;
                if ($keyword->reply_mms) {
                    $reply_mms = true;
                }

                $nestedData['responsive_id'] = '';
                $nestedData['uid']           = $keyword->uid;
                $nestedData['title']         = $keyword->title;
                $nestedData['keyword_name']  = $keyword->keyword_name;
                $nestedData['price']         = "<div>
                                                        <p class='text-bold-600'>".Tool::format_price($keyword->price, $keyword->currency->format)." </p>
                                                        <p class='text-muted'>".$keyword->displayFrequencyTime()."</p>
                                                   </div>";
                $nestedData['status']        = $status;
                $nestedData['is_assigned']   = $is_assigned;

                $nestedData['reply_mms']   = $reply_mms;
                $nestedData['remove_mms']  = __('locale.buttons.remove_mms');
                $nestedData['show_label']  = __('locale.buttons.edit');
                $nestedData['show']        = route('customer.keywords.show', $keyword->uid);
                $nestedData['renew_label'] = __('locale.labels.renew');
                $nestedData['renew']       = route('customer.keywords.pay', $keyword->uid);
                $nestedData['release']     = __('locale.labels.release');
                $data[]                    = $nestedData;

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
     * show available keywords
     *
     * @return Application|Factory|\Illuminate\Contracts\View\View
     * @throws AuthorizationException
     */
    public function buy()
    {
        $this->authorize('buy_keywords');

        $breadcrumbs = [
                ['link' => url('dashboard'), 'name' => __('locale.menu.Dashboard')],
                ['link' => url('keywords'), 'name' => __('locale.menu.Keywords')],
                ['name' => __('locale.keywords.buy_keyword')],
        ];

        return view('customer.keywords.buy', compact('breadcrumbs'));
    }


    /**
     * @param  Request  $request
     *
     * @return void
     * @throws AuthorizationException
     */
    public function available(Request $request)
    {

        $this->authorize('buy_keywords');

        $columns = [
                0 => 'responsive_id',
                1 => 'uid',
                2 => 'uid',
                3 => 'title',
                4 => 'keyword_name',
                5 => 'price',
                6 => 'actions',
        ];

        $totalData = Keywords::where('status', 'available')->count();

        $totalFiltered = $totalData;

        $limit = $request->input('length');
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')];
        $dir   = $request->input('order.0.dir');

        if (empty($request->input('search.value'))) {
            $keywords = Keywords::where('status', 'available')->offset($start)
                    ->limit($limit)
                    ->orderBy($order, $dir)
                    ->get();
        } else {
            $search = $request->input('search.value');

            $keywords = Keywords::where('status', 'available')->whereLike(['uid', 'title', 'keyword_name', 'price'], $search)
                    ->offset($start)
                    ->limit($limit)
                    ->orderBy($order, $dir)
                    ->get();

            $totalFiltered = Keywords::where('status', 'available')->whereLike(['uid', 'title', 'keyword_name', 'price'], $search)->count();
        }

        $data = [];
        if ( ! empty($keywords)) {
            foreach ($keywords as $keyword) {

                $nestedData['responsive_id'] = '';
                $nestedData['uid']           = $keyword->uid;
                $nestedData['title']         = $keyword->title;
                $nestedData['buy']           = __('locale.labels.buy');
                $nestedData['keyword_name']  = $keyword->keyword_name;
                $nestedData['price']         = "<div>
                                                        <p class='text-bold-600'>".Tool::format_price($keyword->price, $keyword->currency->format)." </p>
                                                        <p class='text-muted'>".$keyword->displayFrequencyTime()."</p>
                                                   </div>";
                $nestedData['checkout']      = route('customer.keywords.pay', $keyword->uid);
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
     * View currency for edit
     *
     * @param  Keywords  $keyword
     *
     * @return Application|Factory|View
     *
     * @throws AuthorizationException
     */

    public function show(Keywords $keyword)
    {
        $this->authorize('update_keywords');

        $breadcrumbs = [
                ['link' => url('dashboard'), 'name' => __('locale.menu.Dashboard')],
                ['link' => url('keywords'), 'name' => __('locale.menu.Keywords')],
                ['name' => __('locale.keywords.update_keyword')],
        ];

        if (Auth::user()->customer->getOption('sender_id_verification') == 'yes') {
            $sender_ids    = Senderid::where('user_id', auth()->user()->id)->where('status', 'active')->cursor();
            $phone_numbers = PhoneNumbers::where('user_id', auth()->user()->id)->where('status', 'assigned')->cursor();
        } else {
            $sender_ids    = null;
            $phone_numbers = null;
        }

        return view('customer.keywords.show', compact('breadcrumbs', 'keyword', 'sender_ids', 'phone_numbers'));
    }


    /**
     * @param  Keywords  $keyword
     * @param  CustomerUpdate  $request
     *
     * @return RedirectResponse
     */

    public function update(Keywords $keyword, CustomerUpdate $request): RedirectResponse
    {

        $this->keywords->updateByCustomer($keyword, $request->except('_method', '_token'));

        return redirect()->route('customer.keywords.show', $keyword->uid)->with([
                'status'  => 'success',
                'message' => __('locale.keywords.keyword_successfully_updated'),
        ]);
    }

    /**
     * remove mms file
     *
     * @param  Keywords  $keyword
     *
     * @return JsonResponse
     */

    public function removeMMS(Keywords $keyword): JsonResponse
    {

        if ( ! $keyword->where('user_id', Auth::user()->id)->update(['reply_mms' => null])) {
            return response()->json([
                    'status'  => 'error',
                    'message' => __('locale.exceptions.something_went_wrong'),
            ]);
        }

        return response()->json([
                'status'  => 'success',
                'message' => __('locale.keywords.keyword_mms_file_removed'),
        ]);
    }


    /**
     * @param  Keywords  $keyword
     * @param $id
     *
     * @return JsonResponse Controller|JsonResponse
     *
     * @throws AuthorizationException
     */
    public function release(Keywords $keyword, $id): JsonResponse
    {

        $this->authorize('release_keywords');

        $this->keywords->release($keyword, $id);

        return response()->json([
                'status'  => 'success',
                'message' => __('locale.keywords.keyword_successfully_released'),
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

        $ids      = $request->get('ids');
        $keywords = Keywords::where('user_id', Auth::user()->id)->whereIn('uid', $ids)->cursor();

        foreach ($keywords as $keyword) {
            $keyword->user_id       = 1;
            $keyword->status        = 'available';
            $keyword->validity_date = null;

            $keyword->save();
        }

        return response()->json([
                'status'  => 'success',
                'message' => __('locale.keywords.keyword_successfully_released'),
        ]);

    }


    /**
     * checkout
     *
     * @param  Keywords  $keyword
     *
     * @return Application|Factory|\Illuminate\Contracts\View\View
     * @throws AuthorizationException
     */
    public function pay(Keywords $keyword)
    {

        $this->authorize('buy_keywords');

        $pageConfigs = [
                'bodyClass' => 'ecommerce-application',
        ];

        $breadcrumbs = [
                ['link' => url('dashboard'), 'name' => __('locale.menu.Dashboard')],
                ['link' => url('dashboard'), 'name' => __('locale.menu.Sending')],
                ['link' => url('keywords'), 'name' => __('locale.menu.Keywords')],
                ['name' => __('locale.labels.checkout')],
        ];

        $payment_methods = PaymentMethods::where('status', true)->cursor();

        return view('customer.keywords.checkout', compact('breadcrumbs', 'pageConfigs', 'keyword', 'payment_methods'));
    }


    /**
     * pay sender id payment
     *
     * @param  Keywords  $keyword
     * @param  PayPaymentRequest  $request
     *
     * @return Application|Factory|\Illuminate\Contracts\View\View|RedirectResponse
     */
    public function payment(Keywords $keyword, PayPaymentRequest $request)
    {

        $data = $this->keywords->payPayment($keyword, $request->except('_token'));

        if ($data->getData()->status == 'success') {

            if ($request->payment_methods == 'stripe') {
                return view('customer.Payments.stripe', [
                        'session_id'      => $data->getData()->session_id,
                        'publishable_key' => $data->getData()->publishable_key,
                        'keyword'         => $keyword,
                ]);
            }

            return redirect()->to($data->getData()->redirect_url);
        }

        return redirect()->route('customer.keywords.pay', $keyword->uid)->with([
                'status'  => 'error',
                'message' => $data->getData()->message,
        ]);

    }

}
