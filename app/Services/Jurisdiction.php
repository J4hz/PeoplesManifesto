<?php

namespace App\Services;

use App\Models\IssueCluster;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Scopes every dashboard query to the wards a leader answers for. Policies
 * decide what a user may do. This decides what they are shown.
 */
class Jurisdiction
{
    public function __construct(private readonly User $user) {}

    /**
     * @return Collection<int, int>
     */
    public function wardIds(): Collection
    {
        return $this->user->leader?->office->wardIds() ?? collect();
    }

    /**
     * @return Collection<int, Ward>
     */
    public function wards(): Collection
    {
        return Ward::whereIn('id', $this->wardIds())->orderBy('name')->get();
    }

    /**
     * @return Builder<IssueCluster>
     */
    public function clusters(): Builder
    {
        return IssueCluster::query()->whereIn('ward_id', $this->wardIds())->visible();
    }
}
