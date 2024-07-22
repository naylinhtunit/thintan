<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\HomeController;
use Socialite;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/home';

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function login(Request $request)
    {
        $this->validateLogin($request);

        if ($this->guard()->validate($this->credentials($request))) {
            $user = $this->guard()->getLastAttempted();

            if ($user->is_active && $this->attemptLogin($request)) {
                return $this->sendLoginResponse($request);
            } else {
                return redirect()
                    ->back()
                    ->withInput($request->only($this->username(), 'remember'))
                    ->withErrors(['active' => 'You must be active to login.']);
            }
        }

        return $this->sendFailedLoginResponse($request);
    }

    protected function authenticated(Request $request, $user)
    {
        if ($user->hasRole('instructor')) {
            return redirect()->route('instructor.dashboard');
        } elseif ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        } else {
            return redirect()->route('home');
        }
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $userSocial = Socialite::driver('google')->user();
            $user = User::where('email', $userSocial->getEmail())->first();

            if ($user) {
                Auth::login($user);
            } else {
                $password = bcrypt(Str::random(10));
                $user = User::create([
                    'name' => $userSocial->getName(),
                    'email' => $userSocial->getEmail(),
                    'provider' => 'google',
                    'provider_id' => $userSocial->getId(),
                    'password' => $password,
                ]);
                Auth::login($user);
            }
            return redirect()->action([HomeController::class, 'index']);
        } catch (\Exception $e) {
            return redirect()->route('login');
        }
    }
}
