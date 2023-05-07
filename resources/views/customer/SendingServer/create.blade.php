@extends('layouts/contentLayoutMaster')

@section('title', $server['name'])


@section('content')
    <!-- Basic Vertical form layout section start -->
    <section id="basic-vertical-layouts">
        <div class="row match-height">
            <div class="col-md-6 col-12">

                <form class="form form-vertical"
                    @if (isset($server['id'])) action="{{ route('customer.sending-servers.update', $server['uid']) }}" @else action="{{ route('customer.sending-servers.store') }}" @endif
                    method="post">
                    @if (isset($server['id']))
                        {{ method_field('PUT') }}
                    @endif
                    @csrf

                    {{-- Update Server Credential --}}
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title"> {{ __('locale.sending_servers.update_credentials') }} </h4>
                        </div>
                        <div class="card-content">
                            <div class="card-body">

                                @switch($server['settings'])
                                    @case('Twilio')
                                        <p>{!! __('locale.description.twilio', ['brandname' => config('app.name'), 'url' => route('inbound.twilio')]) !!}</p>
                                    @break
                                @endswitch

                                <div class="form-body">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="mb-1">
                                                <label class="form-label required"
                                                    for="name">{{ __('locale.labels.name') }}</label>
                                                <input type="text" id="name"
                                                    class="form-control @error('name') is-invalid @enderror"
                                                    value="{{ $server['name'] }}" name="name" required>
                                                @error('name')
                                                    <p><small class="text-danger">{{ $message }}</small></p>
                                                @enderror
                                            </div>
                                        </div>

                                        @if ($server['settings'] == 'Twilio')
                                            <div class="col-12">
                                                <div class="mb-1">
                                                    <label class="form-label required" for="account_sid">Account Sid</label>
                                                    <input type="text" id="account_sid"
                                                        class="form-control @error('account_sid') is-invalid @enderror"
                                                        value="{{ $server['account_sid'] }}" name="account_sid" required>
                                                    @error('account_sid')
                                                        <div class="invalid-feedback">
                                                            {{ $message }}
                                                        </div>
                                                    @enderror
                                                </div>
                                            </div>
                                        @endif

                                        @if ($server['settings'] == 'Twilio')
                                            <div class="col-12">
                                                <div class="mb-1">
                                                    <label class="form-label required" for="auth_token">Auth Token</label>
                                                    <input type="text" id="auth_token"
                                                        class="form-control @error('auth_token') is-invalid @enderror"
                                                        value="{{ $server['auth_token'] }}" name="auth_token" required>
                                                    @error('auth_token')
                                                        <div class="invalid-feedback">
                                                            {{ $message }}
                                                        </div>
                                                    @enderror
                                                </div>
                                            </div>
                                        @endif

                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    {{-- Sending Speed and per request sms --}}
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title"> {{ __('locale.sending_servers.sending_limit') }} </h4>
                        </div>
                        <div class="card-content">
                            <div class="card-body">
                                <p>{!! __('locale.description.sending_credit') !!} </p>
                                <div class="form-body">
                                    <div class="row">

                                        <div class="col-12">
                                            <div class="mb-1">
                                                <label class="form-label required"
                                                    for="quota_value">{{ __('locale.sending_servers.sending_limit') }}</label>
                                                <input type="number" id="quota_value"
                                                    class="form-control @error('quota_value') is-invalid @enderror"
                                                    value="{{ $server['quota_value'] }}" name="quota_value" required>
                                                @error('quota_value')
                                                    <p><small class="text-danger">{{ $message }}</small></p>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="mb-1">
                                                <label class="form-label required"
                                                    for="quota_base">{{ __('locale.sending_servers.time_base') }}</label>
                                                <input type="number" id="quota_base"
                                                    class="form-control @error('quota_base') is-invalid @enderror"
                                                    value="{{ $server['quota_base'] }}" name="quota_base" required>
                                                @error('quota_base')
                                                    <p><small class="text-danger">{{ $message }}</small></p>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="mb-1">
                                                <label class="form-label required"
                                                    for="quota_unit">{{ __('locale.sending_servers.time_unit') }}</label>
                                                <select class="form-control" id="quota_unit" name="quota_unit">
                                                    <option value="minute"
                                                        {{ $server['quota_unit'] == 'minute' ? 'selected' : null }}>
                                                        {{ __('locale.labels.minute') }}</option>
                                                    <option value="hour"
                                                        {{ $server['quota_unit'] == 'hour' ? 'selected' : null }}>
                                                        {{ __('locale.labels.hour') }}</option>
                                                    <option value="day"
                                                        {{ $server['quota_unit'] == 'day' ? 'selected' : null }}>
                                                        {{ __('locale.labels.day') }}</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="mb-1">
                                                <label class="form-label required"
                                                    for="sms_per_request">{{ __('locale.sending_servers.per_single_request') }}</label>
                                                <input type="number" id="sms_per_request"
                                                    class="form-control @error('sms_per_request') is-invalid @enderror"
                                                    value="{{ $server['sms_per_request'] }}" name="sms_per_request"
                                                    required>
                                                @error('sms_per_request')
                                                    <p><small class="text-danger">{{ $message }}</small></p>
                                                @enderror
                                            </div>
                                        </div>

                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    {{-- All Predefine features listed here --}}
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">
                                {{ __('locale.sending_servers.available_features') }}
                            </h4>
                        </div>
                        <div class="card-content">
                            <div class="card-body">

                                <div class="form-body">
                                    <div class="row">

                                        <div class="d-flex justify-content-start flex-wrap col-12">

                                            <div class="d-flex flex-column me-1">
                                                <label
                                                    class="form-check-label mb-50">{{ __('locale.labels.schedule') }}</label>
                                                <div class="form-check form-switch form-check-primary">
                                                    <input type="hidden" value="0" name="schedule">
                                                    <input type="checkbox" class="form-check-input" value="1"
                                                        id="schedule" name="schedule"
                                                        {{ $server['schedule'] ? 'checked' : null }}>
                                                    <label class="form-check-label" for="schedule">
                                                        <span class='switch-icon-left'><i data-feather="check"></i>
                                                        </span>
                                                        <span class='switch-icon-right'><i data-feather="x"></i> </span>
                                                    </label>

                                                </div>
                                            </div>

                                            @if ($server['settings'] == 'Twilio')
                                                <div class="d-flex flex-column me-1">
                                                    <label
                                                        class="form-check-label mb-50">{{ __('locale.labels.mms') }}</label>
                                                    <div class="form-check form-switch form-check-primary">
                                                        <input type="hidden" value="0" name="mms">
                                                        <input type="checkbox" class="form-check-input" value="1"
                                                            name="mms" id="mms"
                                                            {{ $server['mms'] ? 'checked' : null }}>
                                                        <label class="form-check-label" for="mms">
                                                            <span class='switch-icon-left'><i data-feather="check"></i>
                                                            </span>
                                                            <span class='switch-icon-right'><i data-feather="x"></i>
                                                            </span>
                                                        </label>
                                                    </div>
                                                </div>
                                            @endif

                                            @if ($server['settings'] == 'Twilio')
                                                <div class="d-flex flex-column me-1">
                                                    <label
                                                        class="form-check-label mb-50">{{ __('locale.labels.two_way') }}</label>
                                                    <div class="form-check form-switch form-check-primary">
                                                        <input type="hidden" value="0" name="two_way">
                                                        <input type="checkbox" class="form-check-input" value="1"
                                                            name="two_way" id="two_way"
                                                            {{ $server['two_way'] ? 'checked' : null }}>
                                                        <label class="form-check-label" for="two_way">
                                                            <span class='switch-icon-left'><i data-feather="check"></i>
                                                            </span>
                                                            <span class='switch-icon-right'><i data-feather="x"></i>
                                                            </span>
                                                        </label>
                                                    </div>
                                                </div>
                                            @endif

                                        </div>

                                        <div class="col-12 mt-2">
                                            <input type="hidden" name="settings" value="{{ $server['settings'] }}">
                                            <input type="hidden" name="type" value="{{ $server['type'] }}">
                                            <button type="submit" class="btn btn-primary mr-1 mb-1"><i
                                                    data-feather="save"></i> {{ __('locale.buttons.save') }} </button>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>


                </form>

            </div>
        </div>
    </section>
    <!-- // Basic Vertical form layout section end -->


@endsection
