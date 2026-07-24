<?php

declare(strict_types=1);

namespace App\Actions\Courses;

use App\Enums\CustomRequestStatus;
use App\Enums\UserRole;
use App\Models\CustomRequest;
use App\Models\User;
use App\Notifications\CustomRequestSubmittedForTeam;
use App\Support\SiteSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class SubmitCustomRequest
{
    /**
     * @param  array{
     *     items: list<array{label: string, quantity: int, budget_cents?: int|null}>,
     *     description?: string|null,
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

        $items = array_values($data['items'] ?? []);

        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => 'Ajoutez au moins un produit à votre demande.',
            ]);
        }

        $notes = trim((string) ($data['description'] ?? ''));
        $aggregates = CustomRequest::aggregatesFromItems($items, $notes !== '' ? $notes : null);

        $request = DB::transaction(function () use ($data, $resolvedUser, $hasSupplier, $items, $aggregates): CustomRequest {
            $request = CustomRequest::query()->create([
                'user_id' => $resolvedUser->id,
                'guest_name' => $data['guest_name'],
                'guest_email' => $data['guest_email'],
                'title' => $aggregates['title'],
                'description' => $aggregates['description'],
                'quantity' => $aggregates['quantity'],
                'budget_cents' => $aggregates['budget_cents'],
                'has_supplier' => $hasSupplier,
                'supplier_name' => $hasSupplier ? ($data['supplier_name'] ?? null) : null,
                'supplier_contact' => $hasSupplier ? ($data['supplier_contact'] ?? null) : null,
                'status' => CustomRequestStatus::Submitted,
            ]);

            foreach ($items as $index => $item) {
                $request->items()->create([
                    'label' => $item['label'],
                    'quantity' => max(1, (int) $item['quantity']),
                    'budget_cents' => $item['budget_cents'] ?? null,
                    'sort_order' => $index,
                ]);
            }

            return $request->load('items');
        });

        $this->notifyTeam($request);

        return $request;
    }

    private function notifyTeam(CustomRequest $request): void
    {
        $admins = User::query()->admins()->get();

        if ($admins->isNotEmpty()) {
            Notification::send($admins, new CustomRequestSubmittedForTeam($request));
        }

        $support = SiteSettings::supportEmail();

        if ($support !== '' && $admins->doesntContain(fn (User $admin) => $admin->email === $support)) {
            Notification::route('mail', $support)
                ->notify(new CustomRequestSubmittedForTeam($request));
        }
    }
}
