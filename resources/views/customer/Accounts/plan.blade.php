@extends('layouts/contentLayoutMaster')

@section('title', __('locale.menu.Subscriptions'))

@section('page-style')
    {{-- Page Css files --}}
    <link rel="stylesheet" type="text/css" href="{{ asset('css/base/pages/page-pricing.css') }}">
@endsection

@section('content')
    <section id="pricing-plan">
        <!-- title text and switch button -->
        {{-- <div class="text-center">
            <h1 class="mt-5">{{ __('locale.plans.pricing') }} {{ __('locale.menu.Plans') }}</h1>
            <p class="mb-2 pb-75">
                {{ __('locale.description.plan_price') }}
            </p>
        </div> --}}
        <!--/ title text and switch button -->

    </section>
@endsection
