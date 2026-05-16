<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class RedirectController extends Controller
{
    public function index(): RedirectResponse
    {
        return Auth::check()
            ? redirect()->route('v2.home')
            : redirect()->route('v2.login');
    }
}
