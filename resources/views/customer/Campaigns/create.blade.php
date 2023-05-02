@extends('layouts/contentLayoutMaster')

@section('title', __('locale.campaigns.create_campaign'))

@section('content')

    <!-- Basic Vertical form layout section start -->
    <section id="basic-vertical-layouts">
        <div class="row match-height">
            <div class="col-md-6 col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('locale.campaigns.create_campaign') }}</h4>
                    </div>
                    <div class="card-content">
                        <div class="card-body">

                            <form class="form form-vertical" action="{{ route('customer.sms.create_campaign') }}"
                                method="post">
                                @csrf

                                <div class="row">

                                    <div class="col-12">
                                        <div class="mb-1">
                                            <label for="campaign_name"
                                                class="form-label">{{ __('locale.labels.campaign_name') }}</label>
                                            <input type="text" id="campaign_name"
                                                class="form-control @error('campaign_name') is-invalid @enderror"
                                                value="{{ old('campaign_name') }}" name="campaign_name" placeholder="{{__('locale.labels.campaign_name')}}" />
                                            @error('campaign_name')
                                                <p><small class="text-danger">{{ $message }}</small></p>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="mb-1">

                                            <div class="btn-group" role="group">
                                                <input type="radio" class="btn-check" name="campaign_type"
                                                    value="single_blast" id="single_blast" autocomplete="off" checked />
                                                <label class="btn btn-outline-primary" for="single_blast">
                                                    {{ __('locale.labels.single_blast') }}</label>

                                                <input type="radio" class="btn-check" name="campaign_type" value="burst"
                                                    id="burst" autocomplete="off" />
                                                <label class="btn btn-outline-primary" for="burst">
                                                    {{ __('locale.labels.burst') }}</label>

                                                <input type="radio" class="btn-check" name="campaign_type" value="scheduled"
                                                    id="scheduled" autocomplete="off" />
                                                <label class="btn btn-outline-primary" for="scheduled">
                                                    {{ __('locale.labels.scheduled') }}</label>

                                                <input type="radio" class="btn-check" name="campaign_type" value="hook"
                                                    id="hook" autocomplete="off" />
                                                <label class="btn btn-outline-primary"
                                                    for="hook">{{ __('locale.labels.hook') }}</label>

                                                <input type="radio" class="btn-check" name="campaign_type"
                                                    value="personal" id="personal" autocomplete="off" />
                                                <label class="btn btn-outline-primary"
                                                    for="personal">{{ __('locale.labels.personal') }}</label>

                                            </div>

                                            @error('campaign_type')
                                                <p><small class="text-danger">{{ $message }}</small></p>
                                            @enderror
                                        </div>

                                    </div>

                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary me-1 mb-1"><i data-feather="save"></i>
                                            {{ __('locale.buttons.create') }}</button>
                                        <button type="reset" class="btn btn-outline-warning mb-1"><i
                                                data-feather="refresh-cw"></i> {{ __('locale.buttons.reset') }}</button>
                                    </div>

                                </div>

                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- // Basic Vertical form layout section end -->

@endsection

@section('page-script')

    <script>
        $(document).ready(function() {

            let firstInvalid = $('form').find('.is-invalid').eq(0);

            if (firstInvalid.length) {
                $('body, html').stop(true, true).animate({
                    'scrollTop': firstInvalid.offset().top - 200 + 'px'
                }, 200);
            }

        });
    </script>
@endsection
