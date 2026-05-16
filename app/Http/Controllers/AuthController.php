<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Providers\RouteServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Validator;
use App\Models\User;
use GuzzleHttp\Client;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    private $log_title;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->log_title = "Login";
        $this->data['set'] = $this->setting();
        $this->setting = Setting::first();
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    function index()
    {
        if (auth()->check()) {
            return redirect('dashboard');
        }
        return view('pages.login');
    }
    /**
     * Login Action
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    function login(Request $request)
    {
        // dd(\Auth::once(['email' => $request->email, 'password' => $request->password]));

        $validator = Validator::make($request->all(), [
            'email' => "required",
            'password' => "required"
        ]);
        if ($validator->fails()) {
            crmlogger('warning', $this->log_title, "Login validation failed", $request->all());
            return back()
                ->withInput()
                ->withErrors($validator);
        }
     //   try {
        if (\Auth::once(['email' => $request->email, 'password' => $request->password])) {

                    $headers = [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json'
                    ];

                    $send_data = [
                        'userDetails' => [
                            'emailId' => $request->email,
                            'password' => $request->password
                        ],
                    ];
                    // dd(json_encode($send_data));
                $client = new Client([
                    'base_uri' => $this->setting['bocsh_link'],
                    'verify' => $this->verifyTls(),
                ]);
                $response = $client->request('POST', '/api/auth/login',
                [
                        'headers' => $headers,
                        'json' => $send_data,
                ]);
                $data = json_decode((string)$response->getBody(),true);
                // dd($data['message']);
                if($data['message'] == 'Token is valid for 1 hour'){
                    \Auth::attempt(['email' => $request->email, 'password' => $request->password]);
                    $user = User::where('email', $request->email)->first();
                    if ($user) {
                        $user->bearer_token = $data['token'];
                        $user->save();
                    }

                    return redirect('/home')
                        ->with('message', 'Welcome back ' . auth()->user()->username)
                        ->with('message_type', 'success');
                }else{
                    return redirect()
                        ->back()
                        ->withErrors(['login' => trans('auth.failed')])
                        ->withInput()
                        ->with('message', trans('auth.failed'))
                        ->with('msg_type', 'warning');
                }
            } else {
                return redirect()
                    ->back()
                    ->withErrors(['login' => trans('auth.failed')])
                    ->withInput()
                    ->with('message', trans('auth.failed'))
                    ->with('msg_type', 'warning');
            }
      //  } catch (\Exception $e) {
    //         return redirect()
  //                  ->back()
//                //    ->withErrors(['login' => 'Token Failed'])
              //      ->withInput()
            //        ->with('message', 'Token Failed')
          //          ->with('msg_type', 'warning');
        //}
    }


    /**
     * Logout
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    function logout(Request $request)
    {
        try {
            $user = User::findorFail(auth()->user()->id);
            \Auth::logout();
            \Session::flush();
            return redirect('/')->with('message', "Logged out successfully!")->with('message_type', 'success');
        } catch (\Exception $e) {
            return back()
            ->withInput()
            ->withErrors('Error In Logout');
        }
    }

    private function verifyTls()
    {
        if (!filter_var(env('TRAVIS_VERIFY_TLS', true), FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        $caBundle = trim((string) env('TRAVIS_CA_BUNDLE', ''));

        if ($caBundle === '') {
            return true;
        }

        if (!is_file($caBundle)) {
            throw new \RuntimeException('Configured Travis CA bundle was not found.');
        }

        return $caBundle;
    }

}
