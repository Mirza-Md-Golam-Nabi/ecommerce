<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Actions\Cart\MergeGuestBag;
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

    public function store(LoginRequest $request, MergeGuestBag $mergeGuestBag): RedirectResponse
    {
        $credentials = $request->only('phone', 'password');

        if (! Auth::guard('customer')->attempt($credentials, $request->boolean('remember', true))) {
            throw ValidationException::withMessages(['phone' => 'মোবাইল নম্বর বা পাসওয়ার্ড ভুল।']);
        }

        $request->session()->regenerate();

        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();
        $mergeGuestBag->handle($customer, $request->cookie(CurrentBag::COOKIE));

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
