<?php

namespace App\Http\Controllers\V2;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class AuthController extends BaseController
{
    public function __construct(\App\Services\V2\ExternalLogisticsService $integrations)
    {
        parent::__construct($integrations);
        $this->middleware('guest')->except('logout');
    }

    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('v2.home');
        }

        return $this->render('auth.login', [
            'pageTitle' => 'Sign In',
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::validate($credentials)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['login' => 'Invalid email or password.'])
                ->with('message', 'Invalid email or password.')
                ->with('message_type', 'danger');
        }

        try {
            $bocsh = $this->integrations->loginToBocsh($credentials['email'], $credentials['password']);
            if (!$this->integrations->loginSucceeded($bocsh)) {
                throw new RuntimeException($bocsh['message'] ?? 'Unable to authenticate with BOCSH.');
            }

            Auth::attempt($credentials);
            $request->session()->regenerate();

            $user = $request->user();
            if ($user) {
                $user->bearer_token = $bocsh['token'];
                $user->save();
                try {
                    $this->integrations->refreshFleetToken($user);
                } catch (\Throwable $exception) {
                    $this->logHandledException($exception, 'FleetX Token Refresh Failed During Login', $request, [
                        'email' => $credentials['email'],
                    ], 'warning');
                    // FleetX is optional for login; the dashboard already falls back cleanly.
                }
            }

            return redirect()->route('v2.home')
                ->with('message', 'Welcome back, ' . ($user ? $user->name : 'user') . '.')
                ->with('message_type', 'success');
        } catch (\Throwable $exception) {
            $this->logHandledException($exception, 'V2 Login Failed', $request, [
                'email' => $credentials['email'],
            ]);
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['login' => $exception->getMessage()])
                ->with('message', $exception->getMessage())
                ->with('message_type', 'danger');
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('v2.login')
            ->with('message', 'Logged out successfully.')
            ->with('message_type', 'success');
    }
}
