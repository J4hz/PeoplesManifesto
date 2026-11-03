<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLeaderRequest;
use App\Http\Requests\Admin\StoreStaffRequest;
use App\Http\Requests\Admin\ToggleUserActiveRequest;
use App\Models\Leader;
use App\Models\Office;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PeopleController extends Controller
{
    public function index(): View
    {
        return view('admin.people', [
            'leaders' => Leader::with(['office', 'user', 'team'])->orderBy('name')->get(),
            'offices' => Office::with('currentLeader')->orderBy('name')->get(),
            'admins' => User::where('role', User::ROLE_ADMIN)->orderBy('name')->get(),
        ]);
    }

    public function storeLeader(StoreLeaderRequest $request, AuditLogger $audit): RedirectResponse
    {
        $user = DB::transaction(function () use ($request) {
            $office = Office::findOrFail($request->integer('office_id'));

            // A new office holder replaces the previous one on the public record.
            $office->leaders()->where('is_current', true)->update(['is_current' => false]);

            $leader = Leader::create([
                'office_id' => $office->id,
                'name' => $request->validated('name'),
                'slug' => Str::slug($request->validated('name').' '.$office->type).'-'.Str::lower(Str::random(4)),
                'term_started_on' => $request->validated('term_started_on'),
                'is_current' => true,
            ]);

            $user = $this->createUser($request->validated('name'), $request->validated('email'), User::ROLE_LEADER, $leader->id);
            $leader->forceFill(['user_id' => $user->id])->save();

            return $user;
        });

        $audit->record('leader.created', $user, after: $user->only(['name', 'email', 'role', 'leader_id']));
        Password::sendResetLink(['email' => $user->email]);

        return back()->with('status', __('admin.people.invited', ['email' => $user->email]));
    }

    public function storeStaff(StoreStaffRequest $request, AuditLogger $audit): RedirectResponse
    {
        $user = $this->createUser($request->validated('name'), $request->validated('email'), User::ROLE_STAFF, $request->integer('leader_id'));

        $audit->record('staff.created', $user, after: $user->only(['name', 'email', 'role', 'leader_id']));
        Password::sendResetLink(['email' => $user->email]);

        return back()->with('status', __('admin.people.invited', ['email' => $user->email]));
    }

    public function toggleActive(ToggleUserActiveRequest $request, User $user, AuditLogger $audit): RedirectResponse
    {
        $before = $user->only('is_active');
        $user->forceFill(['is_active' => $request->boolean('is_active')])->save();
        $audit->record($user->is_active ? 'user.reactivated' : 'user.suspended', $user, $before, $user->only('is_active'));

        return back()->with('status', __($user->is_active ? 'admin.people.reactivated' : 'admin.people.suspended', ['name' => $user->name]));
    }

    /**
     * The random password is never shown to anyone. The person sets their own
     * through the emailed reset link, then has to add an authenticator app.
     */
    private function createUser(string $name, string $email, string $role, int $leaderId): User
    {
        return User::create([
            'name' => $name,
            'email' => Str::lower($email),
            'password' => Str::password(40),
            'role' => $role,
            'leader_id' => $leaderId,
            'is_active' => true,
        ]);
    }
}
