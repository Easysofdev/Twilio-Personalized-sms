<div class="card">
    <div class="card-body py-2 my-25">
        <div class="row">

            <div class="col-12">
                <p> Use a one-time password authenticator on your mobile device or computer to enable <code>two-factor
                        authentication (2FA)</code>.</p>
            </div>

            @if (Auth::user()->two_factor)
                <div class="col-12">
                    <p class="font-medium-2">Status: <span class="text-success">Enabled</span></p>

                    @if (session()->has('backup_code'))
                        <p class="font-medium-2">Here are your backup codes for future use. If you lose your email
                            address, you can use these codes as a backup. Please copy these codes and store them in a
                            safe place.</p>
                        <pre>
                <code class="lang-markup">
                    {{ Auth::user()->two_factor_backup_code }}
                </code>
                </pre>
                    @endif

                    <a href="{{ route('user.account.twofactor.auth', ['status' => 'disabled']) }}"
                        class="btn btn-danger me-2">Disable Two-Factor Authentication</a>
                    <a href="{{ route('user.account.twofactor.generate_code') }}" class="btn btn-primary">Regenerate
                        recovery codes</a>
                </div>
            @else
                <div class="col-12">
                    <p class="font-medium-2">Status: <span class="text-danger">Disabled</span></p>

                    <a href="{{ route('user.account.twofactor.auth', ['status' => 'enable']) }}"
                        class="btn btn-primary">Enable Two-Factor Authentication</a>
                </div>
            @endif


        </div>
    </div>
</div>
