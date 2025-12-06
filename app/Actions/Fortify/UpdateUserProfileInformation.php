<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Domain\User\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'preferred_language' => ['nullable', 'string', 'in:en,fr,de,it'],
        ])->validateWithBag('updateProfileInformation');

        if (
            $input['email'] !== $user->email
            && $user instanceof MustVerifyEmail
        ) {
            $this->updateVerifiedUser($user, $input);
        } else {
            $user->forceFill([
                'first_name' => $input['first_name'],
                'last_name' => $input['last_name'],
                'email' => $input['email'],
                'phone' => $input['phone'] ?? $user->phone,
                'preferred_language' => $input['preferred_language'] ?? $user->preferred_language,
            ])->save();
        }
    }

    /**
     * @param  array<string, mixed>  $input
     */
    protected function updateVerifiedUser(User $user, array $input): void
    {
        $user->forceFill([
            'first_name' => $input['first_name'],
            'last_name' => $input['last_name'],
            'email' => $input['email'],
            'phone' => $input['phone'] ?? $user->phone,
            'preferred_language' => $input['preferred_language'] ?? $user->preferred_language,
            'email_verified_at' => null,
        ])->save();
    }
}
