<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Only active platform admins see the queue dashboard, in every environment.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', fn (?User $user = null) => $user !== null && $user->is_active && $user->isAdmin());
    }

    /**
     * Horizon lets everyone in when running locally unless the gate is used
     * there too. Payloads can carry report text, so it is always gated.
     */
    protected function authorization(): void
    {
        $this->gate();

        Horizon::auth(fn ($request) => Gate::forUser($request->user('web'))->check('viewHorizon'));
    }
}
