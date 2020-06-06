<?php

namespace Lifeonscreen\Google2fa;

use Laravel\Nova\Tool;
use PragmaRX\Google2FA\Google2FA as G2fa;
use PragmaRX\Google2FAQRCode\Google2FA as G2faQRCode;
use PragmaRX\Recovery\Recovery;
use Request;

class Google2fa extends Tool
{
    /**
     * Perform any tasks that need to happen when the tool is booted.
     *
     * @return void
     */
    public function boot()
    {
    }

    public function generateGoogle2faUrl()
    {
        $google2fa = new G2faQRCode();

        return $google2fa->getQRCodeInline(
            config('app.name'),
            auth()->user()->email,
            auth()->user()->google2fa->google2fa_secret
        );
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     * @throws \PragmaRX\Google2FA\Exceptions\InsecureCallException
     */
    public function confirm()
    {
        if (app(Google2FAAuthenticator::class)->isAuthenticated()) {
            auth()->user()->google2fa->google2fa_enable = 1;
            auth()->user()->google2fa->save();

            return response()->redirectTo(config('nova.path'));
        }

        $data['google2fa_url'] = $this->generateGoogle2faUrl();
        $data['error'] = 'Secret is invalid.';

        return view('google2fa::register', $data);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \PragmaRX\Google2FA\Exceptions\InsecureCallException
     */
    public function register()
    {
        $google2fa = new G2faQRCode();

        $data['google2fa_url'] = $this->generateGoogle2faUrl();

        return view('google2fa::register', $data);

    }

    private function isRecoveryValid($recover, $recoveryHashes)
    {
        return in_array($recover,$recoveryHashes);
    }

    /**
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function authenticate()
    {
        if ($recover = Request::get('recover')) {
            if ($this->isRecoveryValid($recover, json_decode(auth()->user()->google2fa->recovery, true)) === false) {
                $data['error'] = 'Recovery key is invalid.';

                return view('google2fa::authenticate', $data);
            }

            $google2fa = new G2fa();
            $recovery = new Recovery();
            $secretKey = $google2fa->generateSecretKey();
            $data['recovery'] = $recovery
                ->setCount(config('lifeonscreen2fa.recovery_codes.count'))
                ->setBlocks(config('lifeonscreen2fa.recovery_codes.blocks'))
                ->setChars(config('lifeonscreen2fa.recovery_codes.chars_in_block'))
                ->toArray();

            $recoveryHashes = $data['recovery'];
            array_walk($recoveryHashes, function (&$value) {
                $value = password_hash($value, config('lifeonscreen2fa.recovery_codes.hashing_algorithm'));
            });

            auth()->user()->google2fa()->delete();
            auth()->user()->google2fa()->create([
                    'google2fa_secret'=>$secretKey,
                    'recovery'=>json_encode($data['recovery']),
                ]
            );

            return response(view('google2fa::recovery', $data));
        }

        if (app(Google2FAAuthenticator::class)->isAuthenticated()) {
            return response()->redirectTo(config('nova.path'));
        }

        $data['error'] = 'One time password is invalid.';

        return view('google2fa::authenticate', $data);
    }
}
