<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Actions\Customer\CompleteCustomerLogin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\LoginRequest;
use App\Models\Customer;
use App\Services\Cart\CurrentBag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(LoginRequest $request, CompleteCustomerLogin $completeLogin): RedirectResponse
    {
        if (! Auth::guard('customer')->attempt($request->only('phone', 'password'), $request->boolean('remember', true))) {
            throw ValidationException::withMessages(['phone' => 'মোবাইল নম্বর বা পাসওয়ার্ড ভুল।']);
        }

        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();
        $completeLogin->handle($customer, $request->session(), $request->cookie(CurrentBag::COOKIE));

        return redirect()->intended(route('home'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
