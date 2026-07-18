<?php

it('renders the home page', function () {
    $this->seed(\Database\Seeders\CatalogSeeder::class);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Home')
        ->has('nextArrival.label')
        ->has('nextArrival.eta')
        ->has('featuredProducts'));
});

it('renders the shop page with seeded catalog', function () {
    $this->seed(\Database\Seeders\CatalogSeeder::class);

    $this->get(route('shop.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Shop/Index')
            ->has('products')
            ->has('products.0.image_url')
            ->has('categories')
            ->where('stats.products', fn ($count) => $count > 0));
});

it('renders the arrivals page with open cargo', function () {
    $this->seed(\Database\Seeders\CatalogSeeder::class);

    $this->get(route('arrivals.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Arrivals/Index')
            ->has('cargos')
            ->has('nextArrival.label'));
});

it('renders the courses page', function () {
    $this->seed(\Database\Seeders\CatalogSeeder::class);

    $this->get(route('courses.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Courses/Index')
            ->missing('examples'));
});
