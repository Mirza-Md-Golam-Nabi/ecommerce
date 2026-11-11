<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Actions\Customer\CompleteCustomerLogin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\RegisterRequest;
use App\Models\Customer;
use App\Services\Cart\CurrentBag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(RegisterRequest $request, CompleteCustomerLogin $completeLogin): RedirectResponse
    {
        $customer = Customer::query()->create($request->validated());

        Auth::guard('customer')->login($customer, remember: true);
        $completeLogin->handle($customer, $request->session(), $request->cookie(CurrentBag::COOKIE));

        return redirect()->intended(route('home'))->with('success', 'স্বাগতম! আপনার অ্যাকাউন্ট তৈরি হয়েছে।');
    }
}
