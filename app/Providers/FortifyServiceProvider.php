<?php

namespace App\Providers;

use App\Actions\Fortify\AttemptToAuthenticate;
use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\RedirectIfTwoFactorConfirmed;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\Company;
use App\Models\GlobalSetting;
use App\Models\LanguageSetting;
use App\Models\SocialAuthSetting;
use App\Models\User;
use App\Scopes\ActiveScope;
use Carbon\Carbon;
use Froiden\Envato\Traits\AppBoot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Actions\EnsureLoginIsNotThrottled;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Features;

class FortifyServiceProvider extends ServiceProvider
{

    use AppBoot;

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Fortify::authenticateThrough(function (Request $request) {

            return array_filter([
                config('fortify.limiters.login') ? null : EnsureLoginIsNotThrottled::class,
                Features::enabled(Features::twoFactorAuthentication()) ? RedirectIfTwoFactorConfirmed::class : null,
                AttemptToAuthenticate::class,
                PrepareAuthenticatedSession::class,
            ]);
        });
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        // Fortify::authenticateThrough();
        Fortify::authenticateUsing(function (Request $request) {
            $rules = [
                'email' => 'required|email:rfc|regex:/(.+)@(.+)\.(.+)/i'
            ];

            $request->validate($rules);

            $user = User::withoutGlobalScope(ActiveScope::class)
                ->where('email', $request->email)
                ->first();


            if ($user && Hash::check($request->password, $user->password)) {

                if ($user->status === 'deactive') {
                    throw ValidationException::withMessages([
                        'email' => __('auth.failedBlocked')
                    ]);
                }

                if ($user->login === 'disable') {
                    throw ValidationException::withMessages([
                        'email' => __('auth.failedLoginDisabled')
                    ]);
                }

                session()->forget('locale');

                return $user;
            }
        });


        Fortify::requestPasswordResetLinkView(function () {
            $globalSetting = GlobalSetting::first();
            App::setLocale($globalSetting->locale);
            Carbon::setLocale($globalSetting->locale);
            setlocale(LC_TIME, $globalSetting->locale . '_' . mb_strtoupper($globalSetting->locale));

            return view('auth.passwords.forget', ['globalSetting' => $globalSetting]);
        });

        Fortify::loginView(function () {

            $this->showInstall();

            $this->checkMigrateStatus();
            $globalSetting = GlobalSetting::first();
            // Is worksuite
            $company = Company::first();

            if (!$this->isLegal()) {
                return redirect('verify-purchase');
            }

            App::setLocale($globalSetting->locale);
            Carbon::setLocale($globalSetting->locale);
            setlocale(LC_TIME, $globalSetting->locale . '_' . mb_strtoupper($globalSetting->locale));

            $userTotal = User::count();

            if ($userTotal == 0) {
                return view('auth.account_setup', ['global' => $globalSetting, 'setting' => $globalSetting]);
            }

            $socialAuthSettings = SocialAuthSetting::first();

            $languages = language_setting();

            return view('auth.login', [
                'globalSetting' => $globalSetting,
                'socialAuthSettings' => $socialAuthSettings,
                'company' => $company,
                'languages' => $languages,
            ]);

        });

        Fortify::resetPasswordView(function ($request) {
            $globalSetting = GlobalSetting::first();
            App::setLocale($globalSetting->locale);
            Carbon::setLocale($globalSetting->locale);
            setlocale(LC_TIME, $globalSetting->locale . '_' . mb_strtoupper($globalSetting->locale));

            return view('auth.passwords.reset-password', ['request' => $request, 'globalSetting' => $globalSetting]);
        });

        Fortify::confirmPasswordView(function ($request) {
            $globalSetting = GlobalSetting::first();
            App::setLocale($globalSetting->locale);
            Carbon::setLocale($globalSetting->locale);
            setlocale(LC_TIME, $globalSetting->locale . '_' . mb_strtoupper($globalSetting->locale));

            return view('auth.password-confirm', ['request' => $request, 'globalSetting' => $globalSetting]);
        });

        Fortify::twoFactorChallengeView(function () {
            $globalSetting = GlobalSetting::first();
            App::setLocale($globalSetting->locale);
            Carbon::setLocale($globalSetting->locale);
            setlocale(LC_TIME, $globalSetting->locale . '_' . mb_strtoupper($globalSetting->locale));

            return view('auth.two-factor-challenge', ['globalSetting' => $globalSetting]);
        });

        Fortify::registerView(function () {

            // ISWORKSUITE
            $company = Company::first();
            $globalSetting = GlobalSetting::first();

            if (!$company->allow_client_signup) {
                return redirect(route('login'));
            }

            App::setLocale($globalSetting->locale);
            Carbon::setLocale($globalSetting->locale);
            setlocale(LC_TIME, $globalSetting->locale . '_' . mb_strtoupper($globalSetting->locale));

            return view('auth.register', ['globalSetting' => $globalSetting]);

        });

    }

    public function checkMigrateStatus()
    {
        return check_migrate_status();
    }

}
