<?php

namespace App\Http\Controllers\Customer;

use App\Exceptions\GeneralException;
use App\Http\Requests\SenderID\CustomSenderID;
use App\Http\Requests\SenderID\PayPaymentRequest;
use App\Library\Tool;
use App\Models\Notifications;
use App\Models\PaymentMethods;
use App\Models\Senderid;
use App\Models\SenderidPlan;
use App\Models\User;
use App\Repositories\Contracts\SenderIDRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SenderIDController extends CustomerBaseController
{


    protected $sender_ids;


    /**
     * SenderIDController constructor.
     *
     * @param  SenderIDRepository  $sender_ids
     */

    public function __construct(SenderIDRepository $sender_ids)
    {
        $this->sender_ids = $sender_ids;
    }

    /**
     * @return Application|Factory|View
     * @throws AuthorizationException
     */

    public function index()
    {

        $this->authorize('view_sender_id');

        $breadcrumbs = [
            ['link' => url('dashboard'), 'name' => __('locale.menu.Dashboard')],
            ['link' => url('dashboard'), 'name' => __('locale.menu.Sending')],
            ['name' => __('locale.menu.Sender ID')],
        ];

        $sender_id_plan = SenderidPlan::count();

        return view('customer.SenderID.index', compact('breadcrumbs', 'sender_id_plan'));
    }


    /**
     * @param  Request  $request
     *
     * @return void
     * @throws AuthorizationException
     */
    public function search(Request $request)
    {

        $this->authorize('view_sender_id');

        $columns = [
            0 => 'responsive_id',
            1 => 'uid',
            2 => 'uid',
            3 => 'sender_id',
            4 => 'price',
            5 => 'status',
            6 => 'action',
        ];

        $totalData = Senderid::where('user_id', Auth::user()->id)->count();

        $totalFiltered = $totalData;

        $limit = $request->input('length');
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')];
        $dir   = $request->input('order.0.dir');

        if (empty($request->input('search.value'))) {
            $sender_ids = Senderid::where('user_id', Auth::user()->id)->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();
        } else {
            $search = $request->input('search.value');

            $sender_ids = Senderid::where('user_id', Auth::user()->id)->whereLike(['uid', 'sender_id', 'price', 'status'], $search)
                ->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $totalFiltered = Senderid::where('user_id', Auth::user()->id)->whereLike(['uid', 'sender_id', 'price', 'status'], $search)->count();
        }

        $data = [];
        if (!empty($sender_ids)) {
            foreach ($sender_ids as $senderid) {

                $is_checkout = false;
                $checkout_label = null;

                if ($senderid->status == 'active') {
                    $status = '<span class="badge bg-success text-uppercase">' . __('locale.labels.active') . '</span>';
                } elseif ($senderid->status == 'pending') {
                    $status = '<span class="badge bg-primary text-uppercase">' . __('locale.labels.pending') . '</span>';
                } elseif ($senderid->status == 'payment_required') {
                    $is_checkout    = true;
                    $checkout_label = __('locale.labels.pay');
                    $status         = '<span class="badge bg-info text-uppercase">' . __('locale.labels.payment_required') . '</span>';
                } elseif ($senderid->status == 'expired') {
                    $is_checkout    = true;
                    $checkout_label = __('locale.labels.renew');
                    $status         = '<span class="badge bg-warning text-uppercase">' . __('locale.labels.expired') . '</span>';
                } else {
                    $status = '<span class="badge bg-danger text-uppercase">' . __('locale.labels.block') . '</span>';
                }


                $nestedData['responsive_id'] = '';
                $nestedData['uid']           = $senderid->uid;
                $nestedData['sender_id']     = $senderid->sender_id;
                $nestedData['price']         = "<div>
                                                        <p class='text-bold-600'>" . Tool::format_price($senderid->price, $senderid->currency->format) . " </p>
                                                        <p class='text-muted'>" . $senderid->displayFrequencyTime() . "</p>
                                                   </div>";
                $nestedData['status']        = $status;
                $nestedData['is_checkout']   = $is_checkout;

                $nestedData['renew_label'] = $checkout_label;
                $nestedData['renew']       = route('customer.senderid.pay', $senderid->uid);
                $nestedData['delete']      = __('locale.buttons.delete');
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
     * request new sender id
     *
     * @return Application|Factory|\Illuminate\Contracts\View\View
     * @throws AuthorizationException
     */
    public function request()
    {
        $this->authorize('create_sender_id');

        $breadcrumbs = [
            ['link' => url('dashboard'), 'name' => __('locale.menu.Dashboard')],
            ['link' => url('dashboard'), 'name' => __('locale.menu.Sending')],
            ['name' => __('locale.menu.Sender ID')],
        ];

        $sender_id_plans = SenderidPlan::cursor();

        return view('customer.SenderID.request_new', compact('breadcrumbs', 'sender_id_plans'));
    }

    /**
     * store custom sender id request
     *
     * @param  CustomSenderID  $request
     *
     * @return RedirectResponse
     */

    public function store(CustomSenderID $request): RedirectResponse
    {

        $this->sender_ids->storeCustom($request->except('_token'));

        $user = User::first();
        
        Notifications::create([
            'user_id'           => $user->id,
            'notification_for'  => 'admin',
            'notification_type' => 'senderid',
            'message'           => 'New Sender ID request from ' . Auth::user()->displayName(),
        ]);

        return redirect()->route('customer.senderid.index')->with([
            'status'  => 'success',
            'message' => __('locale.sender_id.sender_id_successfully_added'),
        ]);
    }

    /**
     * checkout
     *
     * @param  Senderid  $senderid
     *
     * @return Application|Factory|\Illuminate\Contracts\View\View
     * @throws AuthorizationException
     */
    public function pay(Senderid $senderid)
    {

        $this->authorize('create_sender_id');

        $pageConfigs = [
            'bodyClass' => 'ecommerce-application',
        ];

        $breadcrumbs = [
            ['link' => url('dashboard'), 'name' => __('locale.menu.Dashboard')],
            ['link' => url('dashboard'), 'name' => __('locale.menu.Sending')],
            ['link' => url('senderid'), 'name' => __('locale.menu.Sender ID')],
            ['name' => __('locale.labels.checkout')],
        ];

        $payment_methods = PaymentMethods::where('status', true)->cursor();

        return view('customer.SenderID.checkout', compact('breadcrumbs', 'pageConfigs', 'senderid', 'payment_methods'));
    }


    /**
     * pay sender id payment
     *
     * @param  Senderid  $senderid
     * @param  PayPaymentRequest  $request
     *
     * @return Application|Factory|\Illuminate\Contracts\View\View|RedirectResponse
     */
    public function payment(Senderid $senderid, PayPaymentRequest $request)
    {

        $data = $this->sender_ids->payPayment($senderid, $request->except('_token'));

        if (isset($data)) {

            if ($data->getData()->status == 'success') {

                if ($request->payment_methods == 'stripe') {
                    return view('customer.Payments.stripe', [
                        'session_id'      => $data->getData()->session_id,
                        'publishable_key' => $data->getData()->publishable_key,
                        'senderid'        => $senderid,
                    ]);
                }

                return redirect()->to($data->getData()->redirect_url);
            }

            return redirect()->route('customer.senderid.pay', $senderid->uid)->with([
                'status'  => 'error',
                'message' => $data->getData()->message,
            ]);
        }

        return redirect()->route('customer.senderid.pay', $senderid->uid)->with([
            'status'  => 'error',
            'message' => __('locale.exceptions.something_went_wrong'),
        ]);
    }

    /**
     * @param  Senderid  $senderid
     *
     * @return JsonResponse
     *
     * @throws AuthorizationException
     */
    public function destroy(Senderid $senderid): JsonResponse
    {
        $this->authorize('delete_sender_id');

        $this->sender_ids->destroy($senderid, Auth::user()->id);

        return response()->json([
            'status'  => 'success',
            'message' => __('locale.sender_id.sender_id_successfully_deleted'),
        ]);
    }

    /**
     * batch delete
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     * @throws GeneralException
     */
    public function batchAction(Request $request): JsonResponse
    {
        $ids    = $request->get('ids');
        $status = Senderid::where('user_id', Auth::user()->id)->whereIn('uid', $ids)->delete();

        if (!$status) {
            throw new GeneralException(__('locale.exceptions.something_went_wrong'));
        }

        return response()->json([
            'status'  => 'success',
            'message' => __('locale.sender_id.delete_senderids'),
        ]);
    }
}
