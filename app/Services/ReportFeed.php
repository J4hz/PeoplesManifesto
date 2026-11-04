<?php

namespace App\Services;

use App\Models\Submission;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Reports in the order they arrived, for staff. Nothing here loads the identity
 * table, so the list cannot show who sent a report even by mistake.
 */
class ReportFeed
{
    /**
     * @param  Collection<int, int>|null  $wardIds  null means every ward
     */
    public function paginate(?Collection $wardIds, ?int $wardFilter = null, int $perPage = 30): LengthAwarePaginator
    {
        return $this->query($wardIds, $wardFilter)->paginate($perPage)->withQueryString();
    }

    /**
     * @param  Collection<int, int>|null  $wardIds
     * @return Collection<int, Submission>
     */
    public function latest(?Collection $wardIds, int $limit = 5): Collection
    {
        return $this->query($wardIds, null)->limit($limit)->get();
    }

    /**
     * @param  Collection<int, int>|null  $wardIds
     * @return Builder<Submission>
     */
    private function query(?Collection $wardIds, ?int $wardFilter)
    {
        // Only written complaints. A "+" tap has no text and already shows in each issue's resident count.
        return Submission::query()
            ->where('kind', Submission::KIND_REPORT)
            ->with(['ward', 'area', 'cluster'])
            ->when($wardIds !== null, fn ($q) => $q->whereIn('ward_id', $wardIds))
            ->when($wardFilter !== null, fn ($q) => $q->where('ward_id', $wardFilter))
            ->latest('submissions.created_at')
            ->latest('submissions.id');
    }
}
