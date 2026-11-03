<?php

namespace App\Services;

/**
 * Keyed hashes that let us look people up without storing anything readable.
 * The key is separate from APP_KEY so either can be rotated on its own schedule.
 */
class IdentityHasher
{
    public function __construct(private readonly string $key) {}

    public function phone(string $normalisedPhone): string
    {
        return hash_hmac('sha256', 'phone:'.$normalisedPhone, $this->key);
    }

    public function citizen(int $citizenId): string
    {
        return hash_hmac('sha256', 'citizen:'.$citizenId, $this->key);
    }
}
