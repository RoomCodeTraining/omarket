<?php

namespace App\Actions\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;
use App\Notifications\ProductSubmittedForReview;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

final class SubmitProductForReview
{
    public function handle(Product $product, User $actor): Product
    {
        if (! $product->isOwnedBy($actor) && ! $actor->isAdmin()) {
            throw ValidationException::withMessages([
                'product' => 'Vous ne pouvez soumettre que vos propres produits.',
            ]);
        }

        if (! $actor->canPublishProducts()) {
            throw ValidationException::withMessages([
                'product' => 'Votre compte partenaire n’est pas encore autorisé à publier. Contactez Ôhéfê Market.',
            ]);
        }

        if ($product->status !== ProductStatus::Draft) {
            throw ValidationException::withMessages([
                'product' => 'Seuls les brouillons peuvent être soumis pour revue.',
            ]);
        }

        $product->forceFill([
            'status' => ProductStatus::PendingReview,
        ])->save();

        $product = $product->refresh()->loadMissing('owner');

        Notification::send(
            User::query()->admins()->get(),
            new ProductSubmittedForReview($product),
        );

        return $product;
    }
}
