<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\Product;
use App\Services\Cart\CurrentBag;

beforeEach(function () {
    $this->withoutVite();
});

it('registers a customer with a normalized phone and logs them in', function () {
    $this->post('/register', [
        'name' => 'রহিম উদ্দিন',
        'phone' => '+880 1712-345678',
        'password' => 'secret12',
        'password_confirmation' => 'secret12',
    ])->assertRedirect('/');

    $customer = Customer::query()->sole();

    expect($customer->phone)->toBe('01712345678');
    $this->assertAuthenticatedAs($customer, 'customer');
});

it('rejects duplicate, invalid phones and mismatched passwords', function () {
    Customer::factory()->create(['phone' => '01712345678']);

    $this->post('/register', ['name' => 'ক', 'phone' => '01712345678', 'password' => 'secret12', 'password_confirmation' => 'secret12'])
        ->assertSessionHasErrors('phone');

    $this->post('/register', ['name' => 'ক', 'phone' => '12345', 'password' => 'secret12', 'password_confirmation' => 'secret12'])
        ->assertSessionHasErrors('phone');

    $this->post('/register', ['name' => 'ক', 'phone' => '01812345678', 'password' => 'secret12', 'password_confirmation' => 'other123'])
        ->assertSessionHasErrors('password');

    $this->assertGuest('customer');
});

it('logs in with any phone format and logs out', function () {
    $customer = Customer::factory()->create(['phone' => '01712345678', 'password' => 'secret12']);

    $this->post('/login', ['phone' => '০১৭১২৩৪৫৬৭৮', 'password' => 'secret12'])->assertRedirect('/');
    $this->assertAuthenticatedAs($customer, 'customer');

    $this->post('/logout')->assertRedirect('/');
    $this->assertGuest('customer');
});

it('rejects a wrong password', function () {
    Customer::factory()->create(['phone' => '01712345678', 'password' => 'secret12']);

    $this->post('/login', ['phone' => '01712345678', 'password' => 'wrong'])->assertSessionHasErrors('phone');
    $this->assertGuest('customer');
});

it('sends guests to login and back to checkout afterwards', function () {
    $customer = Customer::factory()->create(['password' => 'secret12']);

    $this->get('/checkout')->assertRedirect('/login');

    $this->post('/login', ['phone' => $customer->phone, 'password' => 'secret12'])->assertRedirect('/checkout');
});

it('keeps logged-in customers away from the login page', function () {
    $this->actingAs(Customer::factory()->create(), 'customer')
        ->get('/login')
        ->assertRedirect('/');
});

it('moves the guest bag to a new customer on registration', function () {
    $guestBag = Cart::factory()->create();
    CartItem::factory()->for($guestBag)->create(['quantity' => 2]);

    $this->withCookie(CurrentBag::COOKIE, $guestBag->token)->post('/register', [
        'name' => 'রহিম',
        'phone' => '01712345678',
        'password' => 'secret12',
        'password_confirmation' => 'secret12',
    ]);

    expect($guestBag->fresh()->customer_id)->toBe(Customer::query()->sole()->id);
});

it('merges the guest bag into an existing customer bag on login, keeping the larger quantity', function () {
    $customer = Customer::factory()->create(['password' => 'secret12']);
    $shared = Product::factory()->create();
    $onlyGuest = Product::factory()->create();

    $customerBag = Cart::factory()->create(['customer_id' => $customer->id]);
    CartItem::factory()->for($customerBag)->for($shared)->create(['quantity' => 1]);

    $guestBag = Cart::factory()->create();
    CartItem::factory()->for($guestBag)->for($shared)->create(['quantity' => 3]);
    CartItem::factory()->for($guestBag)->for($onlyGuest)->create(['quantity' => 2]);

    $this->withCookie(CurrentBag::COOKIE, $guestBag->token)
        ->post('/login', ['phone' => $customer->phone, 'password' => 'secret12']);

    expect(Cart::query()->count())->toBe(1)
        ->and($customerBag->items()->pluck('quantity', 'product_id')->all())
        ->toEqual([$shared->id => 3, $onlyGuest->id => 2]);
});
