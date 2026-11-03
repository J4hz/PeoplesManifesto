<?php

namespace Database\Factories;

use App\Models\Leader;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => User::ROLE_STAFF,
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => User::ROLE_ADMIN, 'leader_id' => null]);
    }

    public function leaderFor(Leader $leader): static
    {
        return $this->state(fn () => ['role' => User::ROLE_LEADER, 'leader_id' => $leader->id])
            ->afterCreating(fn (User $user) => $leader->forceFill(['user_id' => $user->id])->save());
    }

    public function staffFor(Leader $leader): static
    {
        return $this->state(fn () => ['role' => User::ROLE_STAFF, 'leader_id' => $leader->id]);
    }

    /**
     * A confirmed authenticator app. The secret is a fixed test value.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn () => [
            'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-one', 'recovery-code-two'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }
}
