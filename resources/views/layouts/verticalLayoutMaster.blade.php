<body
    class="vertical-layout vertical-menu-modern {{ $configData['verticalMenuNavbarType'] }} {{ $configData['blankPageClass'] }} {{ $configData['bodyClass'] }} {{ $configData['sidebarClass'] }} {{ $configData['footerType'] }} {{ $configData['contentLayout'] }}"
    data-open="click" data-menu="vertical-menu-modern"
    data-col="{{ $configData['showMenu'] ? $configData['contentLayout'] : '1-column' }}" data-framework="laravel"
    data-asset-path="{{ asset('/') }}">
    <!-- BEGIN: Header-->
    @include('panels.navbar')
    <!-- END: Header-->

    <!-- BEGIN: Main Menu-->
    @if (isset($configData['showMenu']) && $configData['showMenu'] === true)
        @include('panels.sidebar')
    @endif
    <!-- END: Main Menu-->

    <!-- BEGIN: Content-->
    <div class="app-content content {{ $configData['pageClass'] }}">

        @if (Auth::user()->sms_unit != '-1' && Auth::user()->sms_unit != 0)
            <div class="d-flex align-items-center justify-between py-2">
                <p>{{ __('locale.description.buy_credit') }}</p>
                <a href="{{ route('user.account.top_up') }}" class="btn btn-info mx-2">{{ __('locale.buttons.buy_credit') }}</a>
            </div>
        @endif

        <!-- BEGIN: Header-->
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>

        <div class="content-area-wrapper}}">
            <div class="{{ $configData['sidebarPositionClass'] }}">
                <div class="sidebar">
                    {{-- Include Sidebar Content --}}
                    @yield('content-sidebar')
                </div>
            </div>
            <div class="{{ $configData['contentsidebarClass'] }}">
                <div class="content-wrapper">
                    <div class="content-body">
                        {{-- Include Page Content --}}
                        @yield('content')
                    </div>
                </div>
            </div>
        </div>


    </div>
    <!-- End: Content-->

    <div class="sidenav-overlay"></div>
    <div class="drag-target"></div>

    {{-- include footer --}}
    @include('panels/footer')

    {{-- include default scripts --}}
    @include('panels/scripts')

    <script type="text/javascript">
        $(window).on('load', function() {
            if (feather) {
                feather.replace({
                    width: 14,
                    height: 14
                });
            }
        })
    </script>
</body>

</html>
