<?php

namespace App\Http\Controllers\Customer;

use App\Http\Requests\Campaigns\CampaignBuilderRequest;
use App\Http\Requests\Campaigns\ImportRequest;
use App\Http\Requests\Campaigns\ImportVoiceRequest;
use App\Http\Requests\Campaigns\MMSCampaignBuilderRequest;
use App\Http\Requests\Campaigns\MMSImportRequest;
use App\Http\Requests\Campaigns\MMSQuickSendRequest;
use App\Http\Requests\Campaigns\QuickSendRequest;
use App\Http\Requests\Campaigns\VoiceCampaignBuilderRequest;
use App\Http\Requests\Campaigns\VoiceQuickSendRequest;
use App\Http\Requests\Campaigns\WhatsAppCampaignBuilderRequest;
use App\Http\Requests\Campaigns\WhatsAppQuickSendRequest;
use App\Library\Tool;
use App\Models\Campaigns;
use App\Models\ContactGroups;
use App\Models\CsvData;
use App\Models\PhoneNumbers;
use App\Models\Plan;
use App\Models\PlansCoverageCountries;
use App\Models\PlansSendingServer;
use App\Models\Senderid;
use App\Models\SendingServer;
use App\Models\Templates;
use App\Models\TemplateTags;
use App\Repositories\Contracts\CampaignRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class CampaignController extends CustomerBaseController
{
    protected $campaigns;

    /**
     * CampaignController constructor.
     *
     * @param  CampaignRepository  $campaigns
     */
    public function __construct(CampaignRepository $campaigns)
    {
        $this->campaigns = $campaigns;
    }

    /**
     * quick send message
     *
     * @param  Request  $request
     *
     * @return Application|Factory|View
     * @throws AuthorizationException
     */
    public function quickSend(Request $request)
    {
        $this->authorize('sms_quick_send');

        $breadcrumbs = [
            ['link' => url('dashboard'), 'name' => __('locale.menu.Dashboard')],
            ['name' => __('locale.menu.Send Message')],
        ];

        if (Auth::user()->customer->getOption('sender_id_verification') == 'yes') {
            $sender_ids    = Senderid::where('user_id', auth()->user()->id)->where('status', 'active')->cursor();
            $phone_numbers = PhoneNumbers::where('user_id', auth()->user()->id)->where('status', 'assigned')->cursor();
        } else {
            $sender_ids    = null;
            $phone_numbers = null;
        }

        $recipient = $request->recipient;
        $coverage = PlansCoverageCountries::where('status', true)->cursor();

        $template_tags  = TemplateTags::cursor();
        $contact_groups = ContactGroups::where('status', 1)->where('customer_id', auth()->user()->id)->cursor();

        $templates = Templates::where('user_id', auth()->user()->id)->where('status', 1)->cursor();

        return view('customer.Campaigns.quickSend', compact('breadcrumbs', 'sender_ids', 'phone_numbers', 'recipient', 'coverage', 'template_tags', 'contact_groups', 'templates'));
    }

    /**
     * quick send message
     *
     * @param  Campaigns  $campaign
     * @param  QuickSendRequest  $request
     *
     * @return RedirectResponse
     */
    public function postQuickSend(Campaigns $campaign, QuickSendRequest $request): RedirectResponse
    {
        if (Auth::user()->customer->activeSubscription()) {
            $plan = Plan::where('status', true)->find(Auth::user()->customer->activeSubscription()->plan_id);
            if (!$plan) {
                return redirect()->route('customer.messages.send_message')->with([
                    'status'  => 'error',
                    'message' => 'Purchased plan is not active. Please contact support team.',
                ]);
            }
        }

        $data = $this->campaigns->quickSend($campaign, $request->except('_token'));

        return redirect()->route('customer.reports.sent')->with([
            'status'  => $data->getData()->status,
            'message' => $data->getData()->message,
        ]);
    }

    /**
     * Create a new campaign
     * 
     * @param Request $request
     * 
     * @throws AuthorizationException
     */
    public function createCampaign(Request $request)
    {
        $this->authorize("sms_campaign_builder");

        $breadcrumbs = [
            ['link' => url("dashboard"), 'name' => __('locale.menu.Dashboard')],
            ['link' => url('blacklists'), 'name' => __('locale.menu.SMS')],
            ['name' => __('locale.menu.Create Campaign')],
        ];

        return view('customer.Campaigns.create', compact('breadcrumbs'));
    }


    /**
     * campaign builder
     *
     * @return Application|Factory|View
     * @throws AuthorizationException
     */
    public function campaignBuilder()
    {

        $this->authorize('sms_campaign_builder');

        $breadcrumbs = [
            ['link' => url('dashboard'), 'name' => __('locale.menu.Dashboard')],
            ['link' => url('dashboard'), 'name' => __('locale.menu.Voice')],
            ['name' => __('locale.menu.Campaign Builder')],
        ];

        $template_tags  = TemplateTags::cursor();
        $contact_groups = ContactGroups::where('status', 1)->where('customer_id', auth()->user()->id)->cursor();

        $templates = Templates::where('user_id', auth()->user()->id)->where('status', 1)->cursor();

        return view('customer.Campaigns.campaignBuilder', compact('breadcrumbs', 'template_tags', 'contact_groups', 'templates'));
    }

    /**
     * template info not found
     *
     * @param  Templates  $template
     * @param $id
     *
     * @return JsonResponse
     */
    public function templateData(Templates $template, $id): JsonResponse
    {
        $data = $template->where('user_id', auth()->user()->id)->find($id);
        if ($data) {
            return response()->json([
                'status'  => 'success',
                'message' => $data->message,
            ]);
        }

        return response()->json([
            'status'  => 'error',
            'message' => __('locale.templates.template_info_not_found'),
        ]);
    }

    public function postCreateCampaign(Request $request)
    {
        return redirect()->back()->with(['status' => "error", 'message' => '']);
    }

    /**
     * store campaign
     *
     *
     * @param  Campaigns  $campaign
     * @param  CampaignBuilderRequest  $request
     *
     * @return RedirectResponse
     */
    public function storeCampaign(Campaigns $campaign, CampaignBuilderRequest $request): RedirectResponse
    {
        if (Auth::user()->customer->activeSubscription()) {
            $plan = Plan::where('status', true)->find(Auth::user()->customer->activeSubscription()->plan_id);
            if (!$plan) {
                return redirect()->route('customer.messages.send_message')->with([
                    'status'  => 'error',
                    'message' => 'Purchased plan is not active. Please contact support team.',
                ]);
            }
        }

        $data = $this->campaigns->campaignBuilder($campaign, $request->except('_token'));

        if (isset($data->getData()->status)) {

            if ($data->getData()->status == 'success') {
                return redirect()->route('customer.reports.campaigns')->with([
                    'status'  => 'success',
                    'message' => $data->getData()->message,
                ]);
            }

            return redirect()->route('customer.messages.campaign_builder')->with([
                'status'  => 'error',
                'message' => $data->getData()->message,
            ]);
        }

        return redirect()->route('customer.messages.campaign_builder')->with([
            'status'  => 'error',
            'message' => __('locale.exceptions.something_went_wrong'),
        ]);
    }

    /**
     * send message using file
     *
     * @return Application|Factory|View
     * @throws AuthorizationException
     */
    public function import()
    {
        $this->authorize('sms_bulk_messages');

        $breadcrumbs = [
            ['link' => url('dashboard'), 'name' => __('locale.menu.Dashboard')],
            ['link' => url('dashboard'), 'name' => __('locale.menu.SMS')],
            ['name' => __('locale.menu.Send Using File')],
        ];


        if (Auth::user()->customer->getOption('sender_id_verification') == 'yes') {
            $sender_ids    = Senderid::where('user_id', auth()->user()->id)->where('status', 'active')->cursor();
            $phone_numbers = PhoneNumbers::where('user_id', auth()->user()->id)->where('status', 'assigned')->cursor();
        } else {
            $sender_ids    = null;
            $phone_numbers = null;
        }


        $plan_id = Auth::user()->customer->activeSubscription()->plan_id;

        // Check the customer has permissions using sending servers and has his own sending servers
        if (Auth::user()->customer->getOption('create_sending_server') == 'yes') {
            if (PlansSendingServer::where('plan_id', $plan_id)->count()) {

                $sending_server = SendingServer::where('user_id', Auth::user()->id)->where('plain', 1)->where('status', true)->get();

                if ($sending_server->count() == 0) {
                    $sending_server_ids = PlansSendingServer::where('plan_id', $plan_id)->pluck('sending_server_id')->toArray();
                    $sending_server     = SendingServer::where('plain', 1)->where('status', true)->whereIn('id', $sending_server_ids)->get();
                }
            } else {
                $sending_server_ids = PlansSendingServer::where('plan_id', $plan_id)->pluck('sending_server_id')->toArray();
                $sending_server     = SendingServer::where('plain', 1)->where('status', true)->whereIn('id', $sending_server_ids)->get();
            }
        } else {
            // If customer don't have permission creating sending servers
            $sending_server_ids = PlansSendingServer::where('plan_id', $plan_id)->pluck('sending_server_id')->toArray();
            $sending_server     = SendingServer::where('plain', 1)->where('status', true)->whereIn('id', $sending_server_ids)->get();
        }

        return view('customer.Campaigns.import', compact('breadcrumbs', 'sender_ids', 'phone_numbers', 'sending_server', 'plan_id'));
    }
}
