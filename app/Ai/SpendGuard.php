<?php

namespace App\Ai;

use App\Models\AiUsageLog;

class SpendGuard
{
    public function spentThisMonth(): float
    {
        return (float) AiUsageLog::query()
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('cost_usd');
    }

    public function cap(): float
    {
        return (float) config('ai.spend.monthly_cap_usd');
    }

    /**
     * Briefs are the expensive part, so they stop first.
     */
    public function briefsAllowed(): bool
    {
        return $this->spentThisMonth() < $this->cap();
    }

    /**
     * Intake keeps going past the cap because residents are waiting on it, up to a hard ceiling.
     */
    public function intakeAllowed(): bool
    {
        return $this->spentThisMonth() < $this->cap() * (float) config('ai.spend.intake_ceiling_ratio');
    }
}
