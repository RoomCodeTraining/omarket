<?php

use App\Models\Product;

it('returns the product image when image_path is set', function () {
    $product = new Product(['image_path' => 'images/products/epicerie.svg']);

    expect($product->imageUrl())->toEndWith('/images/products/epicerie.svg');
});

it('falls back to the default image when image_path is empty', function () {
    $product = new Product(['image_path' => null]);

    expect($product->imageUrl())->toEndWith('/'.Product::DEFAULT_IMAGE_PATH);
});
