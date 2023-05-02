<?php // Code within app\Helpers\Helper.php

namespace App\Helpers;

use App\Models\AppConfig;
use App\Models\Contacts;
use App\Models\Language;
use DateTime;
use DateTimeZone;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;

class Helper
{
        /**
         * @return array
         */

        public static function applClasses(): array
        {
                if (config('app.theme_layout_type') == 'vertical') {
                        $data = config('custom.vertical');
                } else {
                        $data = config('custom.horizontal');
                }

                // default data array
                $DefaultData = [
                        'mainLayoutType'         => 'vertical',
                        'theme'                  => 'light',
                        'sidebarCollapsed'       => false,
                        'navbarColor'            => '',
                        'horizontalMenuType'     => 'floating',
                        'verticalMenuNavbarType' => 'floating',
                        'footerType'             => 'static', //footer
                        'layoutWidth'            => 'boxed',
                        'showMenu'               => true,
                        'bodyClass'              => '',
                        'pageClass'              => '',
                        'pageHeader'             => true,
                        'contentLayout'          => 'default',
                        'blankPage'              => false,
                        'defaultLanguage'        => 'en',
                        'direction'              => env('MIX_CONTENT_DIRECTION', 'ltr'),
                ];

                // if any key missing of array from custom.php file it will be merged and set a default value from dataDefault array and store in data variable
                $data = array_merge($DefaultData, $data);

                // All options available in the template
                $allOptions = [
                        'mainLayoutType'         => ['vertical', 'horizontal'],
                        'theme'                  => ['light' => 'light', 'dark' => 'dark-layout', 'bordered' => 'bordered-layout', 'semi-dark' => 'semi-dark-layout'],
                        'sidebarCollapsed'       => [false, true],
                        'showMenu'               => [true, false],
                        'layoutWidth'            => ['full', 'boxed'],
                        'navbarColor'            => ['bg-primary', 'bg-info', 'bg-warning', 'bg-success', 'bg-danger', 'bg-dark'],
                        'horizontalMenuType'     => ['floating' => 'navbar-floating', 'static' => 'navbar-static', 'sticky' => 'navbar-sticky'],
                        'horizontalMenuClass'    => ['static' => '', 'sticky' => 'fixed-top', 'floating' => 'floating-nav'],
                        'verticalMenuNavbarType' => ['floating' => 'navbar-floating', 'static' => 'navbar-static', 'sticky' => 'navbar-sticky', 'hidden' => 'navbar-hidden'],
                        'navbarClass'            => ['floating' => 'floating-nav', 'static' => 'navbar-static-top', 'sticky' => 'fixed-top', 'hidden' => 'd-none'],
                        'footerType'             => ['static' => 'footer-static', 'sticky' => 'footer-fixed', 'hidden' => 'footer-hidden'],
                        'pageHeader'             => [true, false],
                        'contentLayout'          => ['default', 'content-left-sidebar', 'content-right-sidebar', 'content-detached-left-sidebar', 'content-detached-right-sidebar'],
                        'blankPage'              => [false, true],
                        'sidebarPositionClass'   => ['content-left-sidebar' => 'sidebar-left', 'content-right-sidebar' => 'sidebar-right', 'content-detached-left-sidebar' => 'sidebar-detached sidebar-left', 'content-detached-right-sidebar' => 'sidebar-detached sidebar-right', 'default' => 'default-sidebar-position'],
                        'contentsidebarClass'    => ['content-left-sidebar' => 'content-right', 'content-right-sidebar' => 'content-left', 'content-detached-left-sidebar' => 'content-detached content-right', 'content-detached-right-sidebar' => 'content-detached content-left', 'default' => 'default-sidebar'],
                        'defaultLanguage'        => ['en' => 'en', 'fr' => 'fr', 'de' => 'de', 'pt' => 'pt'],
                        'direction'              => ['ltr', 'rtl'],
                ];

                //if mainLayoutType value empty or not match with default options in custom.php config file then set a default value
                foreach ($allOptions as $key => $value) {
                        if (array_key_exists($key, $DefaultData)) {
                                if (gettype($DefaultData[$key]) === gettype($data[$key])) {
                                        // data key should be string
                                        if (is_string($data[$key])) {
                                                // data key should not be empty
                                                if (isset($data[$key]) && $data[$key] !== null) {
                                                        // data key should not be existed inside allOptions array's sub array
                                                        if (!array_key_exists($data[$key], $value)) {
                                                                // ensure that passed value should be match with any of allOptions array value
                                                                $result = array_search($data[$key], $value, 'strict');
                                                                if (empty($result) && $result !== 0) {
                                                                        $data[$key] = $DefaultData[$key];
                                                                }
                                                        }
                                                } else {
                                                        // if data key not set or
                                                        $data[$key] = $DefaultData[$key];
                                                }
                                        }
                                } else {
                                        $data[$key] = $DefaultData[$key];
                                }
                        }
                }

                //layout classes
                $layoutClasses = [
                        'theme'                  => $data['theme'],
                        'layoutTheme'            => $allOptions['theme'][$data['theme']],
                        'sidebarCollapsed'       => $data['sidebarCollapsed'],
                        'showMenu'               => $data['showMenu'],
                        'layoutWidth'            => $data['layoutWidth'],
                        'verticalMenuNavbarType' => $allOptions['verticalMenuNavbarType'][$data['verticalMenuNavbarType']],
                        'navbarClass'            => $allOptions['navbarClass'][$data['verticalMenuNavbarType']],
                        'navbarColor'            => $data['navbarColor'],
                        'horizontalMenuType'     => $allOptions['horizontalMenuType'][$data['horizontalMenuType']],
                        'horizontalMenuClass'    => $allOptions['horizontalMenuClass'][$data['horizontalMenuType']],
                        'footerType'             => $allOptions['footerType'][$data['footerType']],
                        'sidebarClass'           => '',
                        'bodyClass'              => $data['bodyClass'],
                        'pageClass'              => $data['pageClass'],
                        'pageHeader'             => $data['pageHeader'],
                        'blankPage'              => $data['blankPage'],
                        'blankPageClass'         => '',
                        'contentLayout'          => $data['contentLayout'],
                        'sidebarPositionClass'   => $allOptions['sidebarPositionClass'][$data['contentLayout']],
                        'contentsidebarClass'    => $allOptions['contentsidebarClass'][$data['contentLayout']],
                        'mainLayoutType'         => $data['mainLayoutType'],
                        'defaultLanguage'        => $allOptions['defaultLanguage'][$data['defaultLanguage']],
                        'direction'              => $data['direction'],
                ];
                // set default language if session hasn't locale value the set default language
                if (!session()->has('locale')) {
                        app()->setLocale($layoutClasses['defaultLanguage']);
                }

                // sidebar Collapsed
                if ($layoutClasses['sidebarCollapsed'] == 'true') {
                        $layoutClasses['sidebarClass'] = "menu-collapsed";
                }

                // blank page class
                if ($layoutClasses['blankPage'] == 'true') {
                        $layoutClasses['blankPageClass'] = "blank-page";
                }

                return $layoutClasses;
        }

        /**
         * @param $pageConfigs
         *
         * @return bool
         */
        public static function updatePageConfig($pageConfigs): bool
        {
                $demo = 'vertical';
                if (isset($pageConfigs)) {
                        if (count($pageConfigs) > 0) {
                                foreach ($pageConfigs as $config => $val) {
                                        Config::set('custom.' . $demo . '.' . $config, $val);
                                }
                        }
                }

                return false;
        }

        /**
         * @return string
         */
        public static function home_route(): string
        {
                if (Gate::allows('access backend')) {
                        return route('admin.home');
                }

                return route('user.home');
        }

        /**
         * @param  Request  $request
         *
         * @return bool
         */
        public static function is_admin_route(Request $request): bool
        {
                $action = $request->route()->getAction();

                return 'App\Http\Controllers\Admin' === $action['namespace'];
        }

        /**
         * @param  string  $value
         *
         * @return mixed
         */

        public static function app_config($value = '')
        {
                $conf = AppConfig::where('setting', $value)->first();

                return $conf->value;
        }

        /**
         * Get all countries.
         *
         * @return array
         */
        public static function countries(): array
        {
                $countries   = [];
                $countries[] = ['code' => 'GB', 'name' => 'United Kingdom', 'd_code' => '+44'];
                $countries[] = ['code' => 'US', 'name' => 'United States', 'd_code' => '+1'];

                return $countries;
        }


        /**
         * get timezone list
         *
         * @return array
         * @throws Exception
         */
        public static function timezoneList(): array
        {
                $timezoneIdentifiers = DateTimeZone::listIdentifiers();
                $utcTime             = new DateTime('now', new DateTimeZone('UTC'));

                $tempTimezones = [];
                foreach ($timezoneIdentifiers as $timezoneIdentifier) {
                        $currentTimezone = new DateTimeZone($timezoneIdentifier);

                        $tempTimezones[] = [
                                'offset'     => (int) $currentTimezone->getOffset($utcTime),
                                'identifier' => $timezoneIdentifier,
                        ];
                }
                usort($tempTimezones, function ($a, $b) {
                        return ($a['offset'] == $b['offset'])
                                ? strcmp($a['identifier'], $b['identifier'])
                                : $a['offset'] - $b['offset'];
                });

                $timezoneList = [];
                foreach ($tempTimezones as $tz) {
                        $sign                            = ($tz['offset'] > 0) ? '+' : '-';
                        $offset                          = gmdate('H:i', abs($tz['offset']));
                        $timezoneList[$tz['identifier']] = '(UTC ' . $sign . $offset . ') ' .
                                $tz['identifier'];
                }

                return $timezoneList;
        }


        /**
         * Check if exec() function is available.
         *
         * @return bool
         */

        public static function exec_enabled(): bool
        {
                try {
                        // make a small test
                        exec('ls');

                        return function_exists('exec') && !in_array('exec', array_map('trim', explode(', ', ini_get('disable_functions'))));
                } catch (Exception $ex) {
                        return false;
                }
        }

        /**
         * application menu
         *
         * @return array[]
         */
        public static function menuData(): array
        {
                return [
                        "admin"    => [
                                [
                                        "url"    => url(config('app.admin_path') . "/dashboard"),
                                        'slug'   => config('app.admin_path') . "/dashboard",
                                        "name"   => "Dashboard",
                                        "i18n"   => "Dashboard",
                                        "icon"   => "home",
                                        "access" => "access backend",
                                ],
                                [
                                        "url"     => "",
                                        "name"    => "Customer",
                                        "icon"    => "users",
                                        "i18n"    => "Customer",
                                        "access"  => "view customer|view subscription",
                                        "submenu" => [
                                                [
                                                        "url"    => url(config('app.admin_path') . "/customers"),
                                                        'slug'   => config('app.admin_path') . "/customers",
                                                        "name"   => "Customers",
                                                        "i18n"   => "Customers",
                                                        "access" => "view customer",
                                                        "icon"   => "users",
                                                ],
                                                [
                                                        "url"    => url(config('app.admin_path') . "/subscriptions"),
                                                        'slug'   => config('app.admin_path') . "/subscriptions",
                                                        "name"   => "Subscriptions",
                                                        "i18n"   => "Subscriptions",
                                                        "access" => "view subscription",
                                                        "icon"   => "credit-card",
                                                ],
                                        ],
                                ],
                                [
                                        "url"     => "",
                                        "name"    => "Plan",
                                        "i18n"    => "Plan",
                                        "icon"    => "credit-card",
                                        "access"  => "manage plans|manage currencies",
                                        "submenu" => [
                                                [
                                                        "url"    => url(config('app.admin_path') . "/plans"),
                                                        'slug'   => config('app.admin_path') . "/plans",
                                                        "name"   => "Plans",
                                                        "i18n"   => "Plans",
                                                        "access" => "manage plans",
                                                        "icon"   => "credit-card",
                                                ],
                                                [
                                                        "url"    => url(config('app.admin_path') . "/currencies"),
                                                        'slug'   => config('app.admin_path') . "/currencies",
                                                        "name"   => "Currencies",
                                                        "i18n"   => "Currencies",
                                                        "access" => "manage currencies",
                                                        "icon"   => "dollar-sign",
                                                ],
                                        ],
                                ],
                                [
                                        "url"     => "",
                                        "name"    => "Sending",
                                        "icon"    => "send",
                                        "i18n"    => "Sending",
                                        "access"  => "view sender_id|view keywords|view sending_servers|view phone_numbers|view tags",
                                        "submenu" => [
                                                [
                                                        "url"    => url(config('app.admin_path') . "/sending-servers"),
                                                        'slug'   => config('app.admin_path') . "/sending-servers",
                                                        "name"   => "Sending Servers",
                                                        "i18n"   => "Sending Servers",
                                                        "access" => "view sending_servers",
                                                        "icon"   => "send",
                                                ],
                                                [
                                                        "url"    => url(config('app.admin_path') . "/senderid"),
                                                        'slug'   => config('app.admin_path') . "/senderid",
                                                        "name"   => "Sender ID",
                                                        "i18n"   => "Sender ID",
                                                        "access" => "view sender_id",
                                                        "icon"   => "book",
                                                ],
                                                [
                                                        "url"    => url(config('app.admin_path') . "/phone-numbers"),
                                                        'slug'   => config('app.admin_path') . "/phone-numbers",
                                                        "name"   => "Numbers",
                                                        "i18n"   => "Numbers",
                                                        "access" => "view phone_numbers",
                                                        "icon"   => "phone",
                                                ],
                                                [
                                                        "url"    => url(config('app.admin_path') . "/keywords"),
                                                        'slug'   => config('app.admin_path') . "/keywords",
                                                        "name"   => "Keywords",
                                                        "i18n"   => "Keywords",
                                                        "access" => "view keywords",
                                                        "icon"   => "hash",
                                                ],
                                                [
                                                        "url"    => url(config('app.admin_path') . "/tags"),
                                                        'slug'   => config('app.admin_path') . "/tags",
                                                        "name"   => "Template Tags",
                                                        "i18n"   => "Template Tags",
                                                        "access" => "view tags",
                                                        "icon"   => "tag",
                                                ],
                                        ],
                                ],
                                [
                                        "url"     => "",
                                        "name"    => "Security",
                                        "i18n"    => "Security",
                                        "icon"    => "shield",
                                        "access"  => "view blacklist|view spam_word",
                                        "submenu" => [
                                                [
                                                        "url"    => url(config('app.admin_path') . "/blacklists"),
                                                        'slug'   => config('app.admin_path') . "/blacklists",
                                                        "name"   => "Blacklist",
                                                        "i18n"   => "Blacklist",
                                                        "access" => "view blacklist",
                                                        "icon"   => "user-x",
                                                ],
                                                [
                                                        "url"    => url(config('app.admin_path') . "/spam-word"),
                                                        'slug'   => config('app.admin_path') . "/spam-word",
                                                        "name"   => "Spam Word",
                                                        "i18n"   => "Spam Word",
                                                        "access" => "view spam_word",
                                                        "icon"   => "x-square",
                                                ],
                                        ],
                                ],
                                [
                                        "url"     => "",
                                        "name"    => "Administrator",
                                        "i18n"    => "Administrator",
                                        "icon"    => "user",
                                        "access"  => "view administrator|view roles",
                                        "submenu" => [
                                                [
                                                        "url"    => url(config('app.admin_path') . "/administrators"),
                                                        'slug'   => config('app.admin_path') . "/administrators",
                                                        "name"   => "Administrators",
                                                        "i18n"   => "Administrators",
                                                        "access" => "view administrator",
                                                        "icon"   => "users",
                                                ],
                                                [
                                                        "url"    => url(config('app.admin_path') . "/roles"),
                                                        'slug'   => config('app.admin_path') . "/roles",
                                                        "name"   => "Admin Roles",
                                                        "i18n"   => "Admin Roles",
                                                        "access" => "view roles",
                                                        "icon"   => "user-check",
                                                ],
                                        ],
                                ],
                                [
                                        "url"     => "",
                                        "name"    => "Settings",
                                        "i18n"    => "Settings",
                                        "icon"    => "settings",
                                        "access"  => "general settings|view languages|view payment_gateways|view email_templates|manage update_application",
                                        "submenu" => [
                                                [
                                                        "url"    => url(config('app.admin_path') . "/settings"),
                                                        'slug'   => config('app.admin_path') . "/settings",
                                                        "name"   => "All Settings",
                                                        "i18n"   => "All Settings",
                                                        "access" => "general settings",
                                                        "icon"   => "settings",
                                                ],
                                                [
                                                        "url"    => url(config('app.admin_path') . "/countries"),
                                                        'slug'   => config('app.admin_path') . "/countries",
                                                        "name"   => "Countries",
                                                        "i18n"   => "Countries",
                                                        "access" => "general settings",
                                                        "icon"   => "map-pin",
                                                ],
                                                [
                                                        "url"    => url(config('app.admin_path') . "/languages"),
                                                        'slug'   => config('app.admin_path') . "/languages",
                                                        "name"   => "Language",
                                                        "i18n"   => "Language",
                                                        "access" => "view languages",
                                                        "icon"   => "globe",
                                                ],
                                                [
                                                        "url"    => url(config('app.admin_path') . "/payment-gateways"),
                                                        'slug'   => config('app.admin_path') . "/payment-gateways",
                                                        "name"   => "Payment Gateways",
                                                        "i18n"   => "Payment Gateways",
                                                        "access" => "view payment_gateways",
                                                        "icon"   => "shopping-bag",
                                                ],
                                                [
                                                        "url"    => url(config('app.admin_path') . "/email-templates"),
                                                        'slug'   => config('app.admin_path') . "/email-templates",
                                                        "name"   => "Email Templates",
                                                        "i18n"   => "Email Templates",
                                                        "access" => "view email_templates",
                                                        "icon"   => "mail",
                                                ],
                                        ],
                                ],
                                [
                                        "url"     => "",
                                        "name"    => "Reports",
                                        "i18n"    => "Reports",
                                        "icon"    => "bar-chart-2",
                                        "access"  => "view invoices|view sms_history",
                                        "submenu" => [
                                                [
                                                        "url"    => url(config('app.admin_path') . "/invoices"),
                                                        'slug'   => config('app.admin_path') . "/invoices",
                                                        "name"   => "All Invoices",
                                                        "i18n"   => "All Invoices",
                                                        "access" => "view invoices",
                                                        "icon"   => "pie-chart",
                                                ],
                                                [
                                                        "url"    => url(config('app.admin_path') . "/reports"),
                                                        'slug'   => config('app.admin_path') . "/reports",
                                                        "name"   => "SMS History",
                                                        "i18n"   => "SMS History",
                                                        "access" => "view sms_history",
                                                        "icon"   => "bar-chart-2",
                                                ],
                                        ],
                                ],
                        ],
                        "customer" => [
                                [
                                        "url"    => url("dashboard"),
                                        'slug'   => "dashboard",
                                        "name"   => "Dashboard",
                                        "i18n"   => "Dashboard",
                                        "icon"   => "home",
                                        "access" => "access_backend",
                                ],
                                [
                                        "url"    => url("reports/campaigns"),
                                        'slug'   => "campaigns",
                                        "name"   => "Campaigns",
                                        "i18n"   => "Campaigns",
                                        "access" => "view_reports",
                                        "icon"   => "server",
                                ],
                                [
                                        "url"     => "",
                                        "name"    => "Reports",
                                        "i18n"    => "Reports",
                                        "icon"    => "bar-chart-2",
                                        "access"  => "view_reports",
                                        "submenu" => [
                                                [
                                                        "url"    => url("reports/all"),
                                                        'slug'   => "reports/all",
                                                        "name"   => "All Messages",
                                                        "i18n"   => "All Messages",
                                                        "access" => "view_reports",
                                                        "icon"   => "bar-chart-2",
                                                ],
                                                [
                                                        "url"    => url("reports/received"),
                                                        'slug'   => "reports/received",
                                                        "name"   => "Received Messages",
                                                        "i18n"   => "Received Messages",
                                                        "access" => "view_reports",
                                                        "icon"   => "phone-incoming",
                                                ],
                                                [
                                                        "url"    => url("reports/sent"),
                                                        'slug'   => "reports/sent",
                                                        "name"   => "Sent Messages",
                                                        "i18n"   => "Sent Messages",
                                                        "access" => "view_reports",
                                                        "icon"   => "phone-outgoing",
                                                ],
                                        ],
                                ],
                                [
                                        "url"    => url("contacts"),
                                        'slug'   => "contacts",
                                        "name"   => "Contacts",
                                        "i18n"   => "Contacts",
                                        "icon"   => "user",
                                        "access" => "view_contact_group|create_contact_group|update_contact_group|delete_contact_group|view_contact|create_contact|update_contact|delete_contact",
                                ],
                                [
                                        "url"     => "",
                                        "name"    => "Sending",
                                        "icon"    => "send",
                                        "i18n"    => "Sending",
                                        "access"  => "create_sending_servers|view_numbers|view_keywords|view_sender_id|sms_template",
                                        "submenu" => [
                                                [
                                                        "url"    => url("sending-servers"),
                                                        'slug'   => "sending-servers",
                                                        "name"   => "Sending Servers",
                                                        "i18n"   => "Sending Servers",
                                                        "access" => "create_sending_servers",
                                                        "icon"   => "send",
                                                ],
                                                [
                                                        "url"    => url("senderid"),
                                                        'slug'   => "senderid",
                                                        "name"   => "Sender ID",
                                                        "i18n"   => "Sender ID",
                                                        "access" => "view_sender_id",
                                                        "icon"   => "book",
                                                ],
                                                [
                                                        "url"    => url("numbers"),
                                                        'slug'   => "numbers",
                                                        "name"   => "Numbers",
                                                        "i18n"   => "Numbers",
                                                        "access" => "view_numbers",
                                                        "icon"   => "phone",
                                                ],
                                                [
                                                        "url"    => url("keywords"),
                                                        'slug'   => "keywords",
                                                        "name"   => "Keywords",
                                                        "i18n"   => "Keywords",
                                                        "access" => "view_keywords",
                                                        "icon"   => "hash",
                                                ],
                                                [
                                                        "url"    => url("templates"),
                                                        'slug'   => "templates",
                                                        "name"   => "SMS Template",
                                                        "i18n"   => "SMS Template",
                                                        "access" => "sms_template",
                                                        "icon"   => "smartphone",
                                                ],
                                        ],
                                ],
                                [
                                        "url"    => url("blacklists"),
                                        'slug'   => "blacklists",
                                        "name"   => "Blacklist",
                                        "i18n"   => "Blacklist",
                                        "icon"   => "shield",
                                        "access" => "view_blacklist|create_blacklist|update_blacklist|delete_blacklist",
                                ],
                                [
                                        "url"     => "",
                                        "name"    => "SMS",
                                        "i18n"    => "SMS",
                                        "icon"    => "message-square",
                                        "access"  => "sms_campaign_builder|sms_quick_send|sms_bulk_messages",
                                        "submenu" => [
                                                [
                                                        "url"    => url("sms/quick-send"),
                                                        'slug'   => "sms/quick-send",
                                                        "name"   => "Quick Send",
                                                        "i18n"   => "Quick Send",
                                                        "access" => "sms_quick_send",
                                                        "icon"   => "send",
                                                ],
                                                [
                                                        "url"    => url("sms/import"),
                                                        'slug'   => "sms/import",
                                                        "name"   => "Send Using File",
                                                        "i18n"   => "Send Using File",
                                                        "access" => "sms_bulk_messages",
                                                        "icon"   => "file-text",
                                                ],
                                        ],
                                ],
                                [
                                        "url"     => "",
                                        "name"    => "Voice",
                                        "i18n"    => "Voice",
                                        "icon"    => "phone-call",
                                        "access"  => "voice_campaign_builder|voice_quick_send|voice_bulk_messages",
                                        "submenu" => [
                                                [
                                                        "url"    => url("voice/campaign-builder"),
                                                        'slug'   => "voice/campaign-builder",
                                                        "name"   => "Campaign Builder",
                                                        "i18n"   => "Campaign Builder",
                                                        "access" => "voice_campaign_builder",
                                                        "icon"   => "server",
                                                ],
                                                [
                                                        "url"    => url("voice/quick-send"),
                                                        'slug'   => "voice/quick-send",
                                                        "name"   => "Quick Send",
                                                        "i18n"   => "Quick Send",
                                                        "access" => "voice_quick_send",
                                                        "icon"   => "send",
                                                ],
                                                [
                                                        "url"    => url("voice/import"),
                                                        'slug'   => "voice/import",
                                                        "name"   => "Send Using File",
                                                        "i18n"   => "Send Using File",
                                                        "access" => "voice_bulk_messages",
                                                        "icon"   => "file-text",
                                                ],
                                        ],
                                ],
                                [
                                        "url"     => "",
                                        "name"    => "MMS",
                                        "i18n"    => "MMS",
                                        "icon"    => "image",
                                        "access"  => "mms_campaign_builder|mms_quick_send|mms_bulk_messages",
                                        "submenu" => [
                                                [
                                                        "url"    => url("mms/campaign-builder"),
                                                        'slug'   => "mms/campaign-builder",
                                                        "name"   => "Campaign Builder",
                                                        "i18n"   => "Campaign Builder",
                                                        "access" => "mms_campaign_builder",
                                                        "icon"   => "server",
                                                ],
                                                [
                                                        "url"    => url("mms/quick-send"),
                                                        'slug'   => "mms/quick-send",
                                                        "name"   => "Quick Send",
                                                        "i18n"   => "Quick Send",
                                                        "access" => "mms_quick_send",
                                                        "icon"   => "send",
                                                ],
                                                [
                                                        "url"    => url("mms/import"),
                                                        'slug'   => "mms/import",
                                                        "name"   => "Send Using File",
                                                        "i18n"   => "Send Using File",
                                                        "access" => "mms_bulk_messages",
                                                        "icon"   => "file-text",
                                                ],
                                        ],
                                ],
                                [
                                        "url"     => "",
                                        "name"    => "WhatsApp",
                                        "i18n"    => "WhatsApp",
                                        "icon"    => "message-circle",
                                        "access"  => "whatsapp_campaign_builder|whatsapp_quick_send|whatsapp_bulk_messages",
                                        "submenu" => [
                                                [
                                                        "url"    => url("whatsapp/campaign-builder"),
                                                        'slug'   => "whatsapp/campaign-builder",
                                                        "name"   => "Campaign Builder",
                                                        "i18n"   => "Campaign Builder",
                                                        "access" => "whatsapp_campaign_builder",
                                                        "icon"   => "server",
                                                ],
                                                [
                                                        "url"    => url("whatsapp/quick-send"),
                                                        'slug'   => "whatsapp/quick-send",
                                                        "name"   => "Quick Send",
                                                        "i18n"   => "Quick Send",
                                                        "access" => "whatsapp_quick_send",
                                                        "icon"   => "send",
                                                ],
                                                [
                                                        "url"    => url("whatsapp/import"),
                                                        'slug'   => "whatsapp/import",
                                                        "name"   => "Send Using File",
                                                        "i18n"   => "Send Using File",
                                                        "access" => "whatsapp_bulk_messages",
                                                        "icon"   => "file-text",
                                                ],
                                        ],
                                ],
                                [
                                        "url"    => url("chat-box"),
                                        'slug'   => "chat-box",
                                        "name"   => "Chat Box",
                                        "i18n"   => "Chat Box",
                                        "icon"   => "slack",
                                        "access" => "chat_box",
                                ],
                        ],
                ];
        }

        public static function languages()
        {
                $lang_count  = Language::where('status', 1)->count();
                $availLocale = Session::get('available_languages');

                if (!isset($availLocale) || count($availLocale) !== $lang_count) {
                        $availLocale = Language::where('status', 1)->cursor()->map(function ($lang) {
                                return [
                                        'name'     => $lang->name,
                                        'code'     => $lang->code,
                                        'iso_code' => $lang->iso_code,
                                ];
                        })->toArray();

                        session()->put('available_languages', $availLocale);
                }

                return $availLocale;
        }

        public static function greetingMessage()
        {
                /* This sets the $time variable to the current hour in the 24-hour clock format */
                $time = date("H");
                /* If the time is less than 1200 hours, show good morning */
                if ($time < "12") {
                        return __('locale.labels.greeting_message', [
                                'time' => __('locale.labels.good_morning'),
                                'name' => auth()->user()->displayName(),
                        ]);
                } elseif ($time >= "12" && $time < "17") {
                        return __('locale.labels.greeting_message', [
                                'time' => __('locale.labels.good_afternoon'),
                                'name' => auth()->user()->displayName(),
                        ]);
                } elseif ($time >= "17" && $time < "19") {
                        return __('locale.labels.greeting_message', [
                                'time' => __('locale.labels.good_evening'),
                                'name' => auth()->user()->displayName(),
                        ]);
                } else {
                        return __('locale.labels.greeting_message', [
                                'time' => __('locale.labels.good_night'),
                                'name' => auth()->user()->displayName(),
                        ]);
                }
        }

        public static function contactName($number)
        {
                $contact = Contacts::where('phone', $number)->first();

                if ($contact && $contact->first_name != null) {
                        return $contact->first_name . ' ' . $contact->last_name;
                }

                return $number;
        }
}
