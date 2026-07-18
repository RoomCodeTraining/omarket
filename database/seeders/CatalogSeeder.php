<?php

namespace Database\Seeders;

use App\Enums\CargoStatus;
use App\Enums\CustomRequestStatus;
use App\Enums\ProductStatus;
use App\Enums\QuoteStatus;
use App\Models\Cargo;
use App\Models\CargoItem;
use App\Models\Category;
use App\Models\CustomRequest;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@ohefe.test'],
            [
                'name' => 'Admin Ôhéfê',
                'password' => 'password',
                'is_admin' => true,
            ],
        );

        $client = User::query()->updateOrCreate(
            ['email' => 'client@ohefe.test'],
            [
                'name' => 'Awa Kouassi',
                'password' => 'password',
                'is_admin' => false,
            ],
        );

        $categories = collect([
            ['name' => 'Épicerie', 'description' => 'Basiques du placard ivoirien'],
            ['name' => 'Féculents', 'description' => 'Attiéké, placali, farines'],
            ['name' => 'Huiles & condiments', 'description' => 'Huile de palme, piments, épices'],
            ['name' => 'Boissons', 'description' => 'Jus, bières locales, thés'],
            ['name' => 'Beauté & soins', 'description' => 'Beurre de karité et soins naturels'],
        ])->map(function (array $data, int $index) {
            return Category::query()->updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'position' => $index + 1,
                    'is_active' => true,
                ],
            );
        })->keyBy('slug');

        $categoryImages = [
            'epicerie' => 'images/products/epicerie.svg',
            'feculents' => 'images/products/feculents.svg',
            'huiles-condiments' => 'images/products/huiles.svg',
            'boissons' => 'images/products/boissons.svg',
            'beaute-soins' => 'images/products/beaute.svg',
        ];

        $productsData = [
            ['category' => 'epicerie', 'name' => 'Cube Maggi crevette', 'price' => 449, 'stock' => 120, 'unit' => 'sachet', 'featured' => true],
            ['category' => 'epicerie', 'name' => 'Riz parfumé 5 kg', 'price' => 1899, 'stock' => 40, 'unit' => 'sac', 'featured' => true],
            ['category' => 'epicerie', 'name' => 'Concentré de tomate', 'price' => 399, 'stock' => 80, 'unit' => 'boîte', 'featured' => false],
            ['category' => 'feculents', 'name' => 'Attiéké premium 1 kg', 'price' => 1299, 'stock' => 55, 'unit' => 'sachet', 'featured' => true],
            ['category' => 'feculents', 'name' => 'Placali prêt à cuire', 'price' => 999, 'stock' => 30, 'unit' => 'sachet', 'featured' => false],
            ['category' => 'feculents', 'name' => 'Farine de manioc', 'price' => 899, 'stock' => 0, 'unit' => 'kg', 'featured' => false],
            ['category' => 'huiles-condiments', 'name' => 'Huile de palme rouge 1 L', 'price' => 1599, 'stock' => 45, 'unit' => 'bouteille', 'featured' => true],
            ['category' => 'huiles-condiments', 'name' => 'Piment séché moulu', 'price' => 699, 'stock' => 70, 'unit' => 'sachet', 'featured' => false],
            ['category' => 'huiles-condiments', 'name' => 'Soumbala', 'price' => 849, 'stock' => 25, 'unit' => 'unité', 'featured' => false],
            ['category' => 'boissons', 'name' => 'Bissap séché', 'price' => 749, 'stock' => 60, 'unit' => 'sachet', 'featured' => true],
            ['category' => 'boissons', 'name' => 'Gingembre confit pour jus', 'price' => 999, 'stock' => 35, 'unit' => 'bocal', 'featured' => false],
            ['category' => 'beaute-soins', 'name' => 'Beurre de karité brut 250 g', 'price' => 1799, 'stock' => 50, 'unit' => 'pot', 'featured' => true],
            ['category' => 'beaute-soins', 'name' => 'Savon noir artisanal', 'price' => 599, 'stock' => 90, 'unit' => 'unité', 'featured' => false],
        ];

        $products = collect();

        foreach ($productsData as $item) {
            $category = $categories[$item['category']];

            $product = Product::query()->updateOrCreate(
                ['slug' => Str::slug($item['name'])],
                [
                    'category_id' => $category->id,
                    'name' => $item['name'],
                    'description' => "Produit ivoirien authentique : {$item['name']}. Idéal pour la diaspora au Canada.",
                    'price_cents' => $item['price'],
                    'stock_quantity' => $item['stock'],
                    'unit' => $item['unit'],
                    'status' => ProductStatus::Published,
                    'image_path' => $categoryImages[$item['category']] ?? Product::DEFAULT_IMAGE_PATH,
                    'is_featured' => $item['featured'],
                ],
            );

            $products->put($product->slug, $product);
        }

        $openCargo = Cargo::query()->updateOrCreate(
            ['code' => 'CG-ABJ-MTL-0726'],
            [
                'name' => 'Arrivage Juillet — Abidjan',
                'origin' => 'Abidjan',
                'destination' => 'Montréal',
                'status' => CargoStatus::Open,
                'departure_at' => now()->addDays(10)->toDateString(),
                'estimated_arrival_at' => now()->addDays(28)->toDateString(),
                'notes' => 'Réservations ouvertes jusqu’au départ du cargo.',
            ],
        );

        $upcomingCargo = Cargo::query()->updateOrCreate(
            ['code' => 'CG-ABJ-MTL-0826'],
            [
                'name' => 'Arrivage Août — Abidjan',
                'origin' => 'Abidjan',
                'destination' => 'Montréal',
                'status' => CargoStatus::Draft,
                'departure_at' => now()->addDays(40)->toDateString(),
                'estimated_arrival_at' => now()->addDays(58)->toDateString(),
                'notes' => 'Liste produits en préparation.',
            ],
        );

        $cargoLines = [
            ['slug' => 'attieke-premium-1-kg', 'available' => 80, 'reserved' => 12, 'price' => 1199],
            ['slug' => 'huile-de-palme-rouge-1-l', 'available' => 60, 'reserved' => 8, 'price' => 1499],
            ['slug' => 'bissap-seche', 'available' => 100, 'reserved' => 20, 'price' => 699],
            ['slug' => 'beurre-de-karite-brut-250-g', 'available' => 40, 'reserved' => 5, 'price' => 1699],
            ['slug' => 'soumbala', 'available' => 50, 'reserved' => 0, 'price' => 799],
            ['slug' => 'piment-seche-moulu', 'available' => 70, 'reserved' => 15, 'price' => 649],
        ];

        foreach ($cargoLines as $line) {
            $product = $products->get($line['slug']);

            if (! $product) {
                continue;
            }

            CargoItem::query()->updateOrCreate(
                [
                    'cargo_id' => $openCargo->id,
                    'product_id' => $product->id,
                ],
                [
                    'quantity_available' => $line['available'],
                    'quantity_reserved' => $line['reserved'],
                    'unit_price_cents' => $line['price'],
                ],
            );
        }

        // Ensure upcoming cargo is referenced (for admin later)
        unset($upcomingCargo);

        $request = CustomRequest::query()->updateOrCreate(
            [
                'user_id' => $client->id,
                'title' => 'Poisson capitaine fumé',
            ],
            [
                'description' => 'Je cherche du capitaine fumé authentique, environ 2 kg, pour une fête familiale à Montréal.',
                'quantity' => 2,
                'budget_cents' => 8000,
                'status' => CustomRequestStatus::Quoted,
            ],
        );

        Quote::query()->updateOrCreate(
            ['custom_request_id' => $request->id],
            [
                'amount_cents' => 7500,
                'message' => 'Devis tout compris (achat, emballage, import). Validité 7 jours.',
                'status' => QuoteStatus::Sent,
                'valid_until' => now()->addDays(7),
            ],
        );

        CustomRequest::query()->updateOrCreate(
            [
                'user_id' => $client->id,
                'title' => 'Feuilles de gombo séchées',
            ],
            [
                'description' => 'Besoin de feuilles de gombo séchées pour sauce, 3 sachets.',
                'quantity' => 3,
                'budget_cents' => 3500,
                'status' => CustomRequestStatus::Submitted,
            ],
        );

        $this->command?->info("Seeded catalog. Admin: {$admin->email} / Client: {$client->email} (password)");
    }
}
