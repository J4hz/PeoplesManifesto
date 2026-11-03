<x-layout.staff :title="__('admin.people.title')" active="people">
    <div class="page-head">
        <h1>{{ __('admin.people.title') }}</h1>
        <p>{{ __('admin.people.intro') }}</p>
    </div>

    <section class="card stack">
        <h2>{{ __('admin.people.leaders_heading') }}</h2>
        @if ($leaders->isEmpty())
            <p class="muted">{{ __('admin.people.no_leaders') }}</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('admin.people.name') }}</th>
                            <th>{{ __('admin.people.office') }}</th>
                            <th>{{ __('admin.people.accounts') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($leaders as $leader)
                            <tr>
                                <td>
                                    <a href="{{ route('leaders.show', $leader) }}">{{ $leader->name }}</a>
                                    @unless ($leader->is_current) <span class="pill">{{ __('admin.people.former') }}</span> @endunless
                                </td>
                                <td>{{ $leader->office->name }}</td>
                                <td>
                                    @foreach ($leader->team as $member)
                                        <form method="POST" action="{{ route('admin.people.active', $member) }}" class="row">
                                            @csrf
                                            @method('PATCH')
                                            @php
                                                $flags = array_filter([
                                                    __('admin.people.role_'.$member->role),
                                                    $member->is_active ? null : __('admin.people.suspended_label'),
                                                    $member->hasConfirmedTwoFactor() ? null : __('admin.people.no_2fa'),
                                                ]);
                                            @endphp
                                            <span>{{ $member->email }} <span class="muted small">({{ implode(', ', $flags) }})</span></span>
                                            <input type="hidden" name="is_active" value="{{ $member->is_active ? 0 : 1 }}">
                                            <button type="submit" class="btn btn-quiet btn-small">{{ $member->is_active ? __('admin.people.suspend') : __('admin.people.reactivate') }}</button>
                                        </form>
                                    @endforeach
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <div class="grid-2">
        <section class="card stack">
            <h2>{{ __('admin.people.add_leader') }}</h2>
            <p class="small muted">{{ __('admin.people.add_leader_hint') }}</p>
            <form method="POST" action="{{ route('admin.people.leaders.store') }}" class="stack" novalidate>
                @csrf
                <div class="field">
                    <label for="office_id">{{ __('admin.people.office') }}</label>
                    <select id="office_id" name="office_id" required>
                        @foreach ($offices as $office)
                            <option value="{{ $office->id }}" @selected((int) old('office_id') === $office->id)>{{ $office->name }}@if ($office->currentLeader) ({{ $office->currentLeader->name }}) @endif</option>
                        @endforeach
                    </select>
                    <x-field-error name="office_id" />
                </div>
                <div class="field">
                    <label for="leader-name">{{ __('admin.people.name') }}</label>
                    <input id="leader-name" name="name" type="text" required maxlength="120">
                    <x-field-error name="name" />
                </div>
                <div class="field">
                    <label for="leader-email">{{ __('admin.people.email') }}</label>
                    <input id="leader-email" name="email" type="email" required>
                    <x-field-error name="email" />
                </div>
                <div class="field">
                    <label for="term_started_on">{{ __('admin.people.term_start') }} <span class="hint">{{ __('citizen.raise.optional') }}</span></label>
                    <input id="term_started_on" name="term_started_on" type="date">
                </div>
                <button type="submit" class="btn">{{ __('admin.people.invite') }}</button>
            </form>
        </section>

        <section class="card stack">
            <h2>{{ __('admin.people.add_staff') }}</h2>
            <p class="small muted">{{ __('admin.people.add_staff_hint') }}</p>
            <form method="POST" action="{{ route('admin.people.staff.store') }}" class="stack" novalidate>
                @csrf
                <div class="field">
                    <label for="leader_id">{{ __('admin.people.works_for') }}</label>
                    <select id="leader_id" name="leader_id" required>
                        @foreach ($leaders->where('is_current', true) as $leader)
                            <option value="{{ $leader->id }}">{{ $leader->name }}, {{ $leader->office->name }}</option>
                        @endforeach
                    </select>
                    <x-field-error name="leader_id" />
                </div>
                <div class="field">
                    <label for="staff-name">{{ __('admin.people.name') }}</label>
                    <input id="staff-name" name="name" type="text" required maxlength="120">
                </div>
                <div class="field">
                    <label for="staff-email">{{ __('admin.people.email') }}</label>
                    <input id="staff-email" name="email" type="email" required>
                </div>
                <button type="submit" class="btn">{{ __('admin.people.invite') }}</button>
            </form>
        </section>
    </div>

    <section class="card stack">
        <h2>{{ __('admin.people.admins_heading') }}</h2>
        <ul>
            @foreach ($admins as $admin)
                <li>{{ $admin->name }} <span class="muted small">{{ $admin->email }}</span></li>
            @endforeach
        </ul>
        <p class="small muted">{{ __('admin.people.admins_hint') }}</p>
    </section>
</x-layout.staff>
