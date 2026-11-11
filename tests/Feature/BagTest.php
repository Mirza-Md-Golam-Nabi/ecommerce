<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Services\Cart\CurrentBag;
use App\Settings\StoreSettings;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withCredentials();
    $this->product = Product::factory()->for(Category::factory())->create(['price' => 8500]);
});

it('creates a guest bag and sets the cookie on first add', function () {
    $response = $this->putJson("/bag/items/{$this->product->id}", ['quantity' => 2])
        ->assertOk()
        ->assertJsonPath('bag.count', 2)
        ->assertJsonPath('bag.subtotal', 17000)
        ->assertJsonPath("bag.items.{$this->product->id}", 2)
        ->assertCookie(CurrentBag::COOKIE);

    expect(Cart::query()->count())->toBe(1)
        ->and($response->getCookie(CurrentBag::COOKIE)->getValue())->toBe(Cart::query()->value('token'));
});

it('updates and removes items in an existing bag', function () {
    $cart = Cart::factory()->create();
    CartItem::factory()->for($cart)->for($this->product)->create(['quantity' => 1]);

    $this->withCookie(CurrentBag::COOKIE, $cart->token)
        ->putJson("/bag/items/{$this->product->id}", ['quantity' => 5])
        ->assertJsonPath('bag.count', 5);

    $this->withCookie(CurrentBag::COOKIE, $cart->token)
        ->putJson("/bag/items/{$this->product->id}", ['quantity' => 0])
        ->assertJsonPath('bag.count', 0);

    expect(CartItem::query()->count())->toBe(0)
        ->and(Cart::query()->count())->toBe(1);
});

it('does not create a bag when removing without one', function () {
    $this->putJson("/bag/items/{$this->product->id}", ['quantity' => 0])
        ->assertOk()
        ->assertJsonPath('bag.count', 0);

    expect(Cart::query()->count())->toBe(0);
});

it('rejects unavailable, inactive and over-limit products', function () {
    $unavailable = Product::factory()->unavailable()->create();
    $inactive = Product::factory()->inactive()->create();
    $limited = Product::factory()->create(['max_per_order' => 3]);

    $this->putJson("/bag/items/{$unavailable->id}", ['quantity' => 1])->assertUnprocessable()->assertJsonValidationErrors('quantity');
    $this->putJson("/bag/items/{$inactive->id}", ['quantity' => 1])->assertUnprocessable();
    $this->putJson("/bag/items/{$limited->id}", ['quantity' => 4])->assertUnprocessable();
    $this->putJson("/bag/items/{$limited->id}", ['quantity' => 3])->assertOk();
});

it('applies the store-wide quantity limit', function () {
    app(StoreSettings::class)->save(['default_max_qty_per_product' => 2]);

    $this->putJson("/bag/items/{$this->product->id}", ['quantity' => 3])->assertUnprocessable();
});

it('validates the quantity input', function () {
    $this->putJson("/bag/items/{$this->product->id}", ['quantity' => -1])->assertUnprocessable();
    $this->putJson("/bag/items/{$this->product->id}", [])->assertUnprocessable();
});

it('returns bag details with delivery charge and excludes items no longer orderable', function () {
    app(StoreSettings::class)->save(['default_delivery_charge' => 6000]);

    $cart = Cart::factory()->create();
    CartItem::factory()->for($cart)->for($this->product)->create(['quantity' => 2]);
    $gone = Product::factory()->create(['price' => 10000]);
    CartItem::factory()->for($cart)->for($gone)->create(['quantity' => 1]);
    $gone->update(['is_available' => false]);

    $this->withCookie(CurrentBag::COOKIE, $cart->token)
        ->getJson('/bag')
        ->assertOk()
        ->assertJsonCount(2, 'items')
        ->assertJsonPath('items.1.can_be_ordered', false)
        ->assertJsonPath('items.1.line_total', 0)
        ->assertJsonPath('subtotal', 17000)
        ->assertJsonPath('delivery_charge', 6000)
        ->assertJsonPath('total', 23000);
});

it('charges no delivery for an empty bag', function () {
    $this->getJson('/bag')
        ->assertOk()
        ->assertJsonPath('subtotal', 0)
        ->assertJsonPath('delivery_charge', 0);
});

it('can remove a product that was deleted after being added', function () {
    $cart = Cart::factory()->create();
    CartItem::factory()->for($cart)->for($this->product)->create(['quantity' => 1]);
    $this->product->delete();

    $this->withCookie(CurrentBag::COOKIE, $cart->token)
        ->putJson("/bag/items/{$this->product->id}", ['quantity' => 0])
        ->assertOk()
        ->assertJsonPath('bag.count', 0);
});

it('prunes guest bags untouched for 30 days', function () {
    $old = Cart::factory()->create(['updated_at' => now()->subDays(31)]);
    $recent = Cart::factory()->create(['updated_at' => now()->subDays(5)]);

    $this->artisan('model:prune', ['--model' => [Cart::class]])->assertSuccessful();

    expect(Cart::query()->pluck('id')->all())->toBe([$recent->id])
        ->and(Cart::query()->whereKey($old->id)->exists())->toBeFalse();
});

it('shares the bag summary with every page', function () {
    $cart = Cart::factory()->create();
    CartItem::factory()->for($cart)->for($this->product)->create(['quantity' => 3]);

    $this->withCookie(CurrentBag::COOKIE, $cart->token)
        ->get('/')
        ->assertInertia(fn (Assert $page) => $page
            ->where('bag.count', 3)
            ->where('bag.subtotal', 25500));
});
