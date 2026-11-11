<?php

use App\Models\Category;
use App\Models\Product;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {

    $this->food = Category::factory()->onHome(2)->create(['name' => 'খাবার', 'slug' => 'food']);
    $this->spices = Category::factory()->childOf($this->food)->create(['name' => 'মসলা', 'slug' => 'spices']);
});

it('shows visible popular categories on the home page', function () {
    Category::factory()->inactive()->onHome(1)->create(['slug' => 'hidden']);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->has('categories', 1)
            ->where('categories.0.slug', 'food'));
});

it('shares the visible category tree for the sidebar', function () {
    Category::factory()->inactive()->create(['slug' => 'hidden']);

    $this->get('/')
        ->assertInertia(fn (Assert $page) => $page
            ->has('navigation', 2)
            ->where('navigation.0.slug', 'food'));
});

it('shows sub-category tiles for a category with children', function () {
    $this->get('/food')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Category')
            ->where('category.slug', 'food')
            ->has('subcategories', 1)
            ->where('subcategories.0.slug', 'spices')
            ->where('products', null));
});

it('lists products of a leaf category with unavailable ones last', function () {
    Product::factory()->for($this->spices)->unavailable()->create(['name' => 'ক', 'slug' => 'a-unavailable']);
    Product::factory()->for($this->spices)->create(['name' => 'খ', 'slug' => 'b-available']);
    Product::factory()->for($this->spices)->inactive()->create(['slug' => 'c-inactive']);

    $this->get('/spices')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Category')
            ->where('breadcrumbs.0.slug', 'food')
            ->where('breadcrumbs.1.slug', 'spices')
            ->has('products.data', 2)
            ->where('products.data.0.slug', 'b-available')
            ->where('products.data.1.slug', 'a-unavailable'));
});

it('sorts products by price', function () {
    Product::factory()->for($this->spices)->create(['slug' => 'expensive', 'price' => 50000]);
    Product::factory()->for($this->spices)->create(['slug' => 'cheap', 'price' => 1000]);

    $this->get('/spices?sort=price_desc')
        ->assertInertia(fn (Assert $page) => $page
            ->where('sort', 'price_desc')
            ->where('products.data.0.slug', 'expensive'));

    $this->get('/spices?sort=price_asc')
        ->assertInertia(fn (Assert $page) => $page->where('products.data.0.slug', 'cheap'));
});

it('shows a product page', function () {
    Product::factory()->for($this->spices)->create([
        'slug' => 'radhuni-holud',
        'price' => 8500,
        'compare_at_price' => 10000,
        'description' => 'খাঁটি হলুদ',
    ]);

    $this->get('/radhuni-holud')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Product')
            ->where('product.slug', 'radhuni-holud')
            ->where('product.discount_percent', 15)
            ->where('product.description', 'খাঁটি হলুদ')
            ->where('breadcrumbs.1.slug', 'spices'));
});

it('returns 404 for hidden or unknown pages', function () {
    $hiddenCategory = Category::factory()->inactive()->create(['slug' => 'hidden-category']);
    Product::factory()->for($hiddenCategory)->create(['slug' => 'in-hidden-category']);
    Product::factory()->for($this->spices)->inactive()->create(['slug' => 'inactive-product']);

    $this->get('/hidden-category')->assertNotFound();
    $this->get('/in-hidden-category')->assertNotFound();
    $this->get('/inactive-product')->assertNotFound();
    $this->get('/does-not-exist')->assertNotFound();
});

it('returns quick view details as json', function () {
    Product::factory()->for($this->spices)->create(['slug' => 'radhuni-holud', 'description' => 'খাঁটি হলুদ']);

    $this->getJson('/quick-view/radhuni-holud')
        ->assertOk()
        ->assertJsonPath('product.slug', 'radhuni-holud')
        ->assertJsonPath('product.description', 'খাঁটি হলুদ');
});

it('searches by bangla name and by english keywords', function () {
    Product::factory()->for($this->spices)->create(['name' => 'রাঁধুনি হলুদ গুঁড়া', 'slug' => 'holud', 'search_keywords' => 'turmeric']);
    Product::factory()->for($this->spices)->create(['name' => 'চিনি', 'slug' => 'chini', 'search_keywords' => 'sugar']);
    Product::factory()->for($this->spices)->inactive()->create(['name' => 'হলুদ পুরনো', 'slug' => 'old-holud']);

    $this->get('/search?q=হলুদ')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Search')
            ->where('query', 'হলুদ')
            ->has('products.data', 1)
            ->where('products.data.0.slug', 'holud'));

    $this->get('/search?q=sugar')
        ->assertInertia(fn (Assert $page) => $page->where('products.data.0.slug', 'chini'));
});

it('shows an empty search page without a query', function () {
    $this->get('/search')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('products', null));
});
