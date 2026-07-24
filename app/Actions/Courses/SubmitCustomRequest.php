<?php

declare(strict_types=1);

namespace App\Actions\Courses;

use App\Enums\CustomRequestStatus;
use App\Enums\UserRole;
use App\Models\CustomRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class SubmitCustomRequest
{
    /**
     * @param  array{
     *     title: string,
     *     description: string,
     *     quantity: int,
     *     budget_cents?: int|null,
     *     guest_name: string,
     *     guest_email: string,
     *     has_supplier: bool,
     *     supplier_name?: string|null,
     *     supplier_contact?: string|null
     * }  $data
     */
    public function handle(array $data, ?User $user = null): CustomRequest
    {
        $resolvedUser = $user;

        if (! $resolvedUser) {
            $existing = User::query()->where('email', $data['guest_email'])->first();

            if ($existing && $existing->role !== UserRole::Client) {
                throw ValidationException::withMessages([
                    'guest_email' => 'Cet e-mail est déjà utilisé pour un compte partenaire ou admin.',
                ]);
            }

            $resolvedUser = $existing ?? User::query()->create([
                'name' => $data['guest_name'],
                'email' => $data['guest_email'],
                'password' => Hash::make(Str::random(32)),
                'role' => UserRole::Client,
                'is_admin' => false,
                'can_publish' => false,
            ]);
        }

        $hasSupplier = (bool) ($data['has_supplier'] ?? false);

        if ($hasSupplier && blank($data['supplier_name'] ?? null)) {
            throw ValidationException::withMessages([
                'supplier_name' => 'Indiquez le nom du fournisseur, ou précisez que vous n’en avez pas.',
            ]);
        }

        return CustomRequest::query()->create([
            'user_id' => $resolvedUser->id,
            'guest_name' => $data['guest_name'],
            'guest_email' => $data['guest_email'],
            'title' => $data['title'],
            'description' => $data['description'],
            'quantity' => $data['quantity'],
            'budget_cents' => $data['budget_cents'] ?? null,
            'has_supplier' => $hasSupplier,
            'supplier_name' => $hasSupplier ? ($data['supplier_name'] ?? null) : null,
            'supplier_contact' => $hasSupplier ? ($data['supplier_contact'] ?? null) : null,
            'status' => CustomRequestStatus::Submitted,
        ]);
    }
}
