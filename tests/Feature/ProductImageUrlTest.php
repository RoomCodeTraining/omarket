<?php

use App\Models\Product;
use Illuminate\Support\Facades\URL;

it('returns the product image when image_path is a public asset', function () {
    URL::forceRootUrl('https://omarket.test');

    $product = new Product(['image_path' => 'images/products/epicerie.svg']);

    expect($product->imageUrl())->toBe('https://omarket.test/images/products/epicerie.svg');
});

it('returns a storage url for uploaded product images', function () {
    URL::forceRootUrl('https://omarket.test');

    $product = new Product(['image_path' => 'products/attiéké.jpg']);

    expect($product->imageUrl())->toBe('https://omarket.test/storage/products/attiéké.jpg');
});

it('falls back to the default image when image_path is empty', function () {
    URL::forceRootUrl('https://omarket.test');

    $product = new Product(['image_path' => null]);

    expect($product->imageUrl())->toBe('https://omarket.test/'.Product::DEFAULT_IMAGE_PATH);
});
