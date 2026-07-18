<?php

namespace App\Actions\Courses;

use App\Enums\CustomRequestStatus;
use App\Models\CustomRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class SubmitCustomRequest
{
    /**
     * @param  array{title: string, description: string, quantity: int, budget_cents?: int|null, guest_name: string, guest_email: string}  $data
     */
    public function handle(array $data, ?User $user = null): CustomRequest
    {
        $resolvedUser = $user;

        if (! $resolvedUser) {
            $resolvedUser = User::query()->firstOrCreate(
                ['email' => $data['guest_email']],
                [
                    'name' => $data['guest_name'],
                    'password' => Hash::make(Str::random(32)),
                    'is_admin' => false,
                ],
            );
        }

        return CustomRequest::query()->create([
            'user_id' => $resolvedUser->id,
            'guest_name' => $data['guest_name'],
            'guest_email' => $data['guest_email'],
            'title' => $data['title'],
            'description' => $data['description'],
            'quantity' => $data['quantity'],
            'budget_cents' => $data['budget_cents'] ?? null,
            'status' => CustomRequestStatus::Submitted,
        ]);
    }
}
