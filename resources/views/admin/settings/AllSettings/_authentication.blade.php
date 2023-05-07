<div class="col-md-6 col-12">
    <div class="form-body">
        <form class="form form-vertical" action="{{ route('admin.settings.authentication') }}" method="post">
            @csrf
            <div class="row">

                <div class="col-12">
                    <div class="mb-1">
                        <label for="client_registration"
                            class="form-label required">{{ __('locale.settings.client_registration') }}</label>
                        <select class="form-select" id="client_registration" name="client_registration">
                            <option value="1" @if (config('account.can_register') == true) selected @endif>
                                {{ __('locale.labels.yes') }}</option>
                            <option value="0" @if (config('account.can_register') == false) selected @endif>
                                {{ __('locale.labels.no') }}</option>
                        </select>
                    </div>
                    @error('client_registration')
                        <p><small class="text-danger">{{ $message }}</small></p>
                    @enderror
                </div>

                <div class="col-12">
                    <div class="mb-1">
                        <label for="registration_verification"
                            class="form-label required">{{ __('locale.settings.registration_verification') }}</label>
                        <select class="form-select" id="registration_verification" name="registration_verification">
                            <option value="1" @if (config('account.verify_account') == true) selected @endif>
                                {{ __('locale.labels.yes') }}</option>
                            <option value="0" @if (config('account.verify_account') == false) selected @endif>
                                {{ __('locale.labels.no') }}</option>
                        </select>
                    </div>
                    @error('registration_verification')
                        <p><small class="text-danger">{{ $message }}</small></p>
                    @enderror
                </div>

                <div class="col-12">
                    <div class="mb-1">
                        <label for="two_factor"
                            class="form-label required">{{ __('locale.settings.two_factor_authentication') }}</label>
                        <select class="form-select" id="two_factor" name="two_factor">
                            <option value="1" @if (config('app.two_factor') == true) selected @endif>
                                {{ __('locale.labels.yes') }}</option>
                            <option value="0" @if (config('app.two_factor') == false) selected @endif>
                                {{ __('locale.labels.no') }}</option>
                        </select>
                    </div>
                    @error('two_factor')
                        <p><small class="text-danger">{{ $message }}</small></p>
                    @enderror
                </div>

                <div class="col-12 show-two-factor">
                    <input type="hidden" value="email" name="two_factor_send_by">
                </div>
            </div>
        </form>
    </div>
</div>
