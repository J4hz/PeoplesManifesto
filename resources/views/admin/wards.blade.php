<x-layout.staff :title="__('admin.wards.title')" active="wards">
    <div class="page-head">
        <h1>{{ __('admin.wards.title') }}</h1>
        <p>{{ __('admin.wards.intro') }}</p>
    </div>

    @foreach ($counties as $county)
        @foreach ($county->constituencies as $constituency)
            <section class="stack">
                <h2>{{ __('citizen.welcome.constituency', ['constituency' => $constituency->name, 'county' => $county->name]) }}</h2>
                @foreach ($constituency->wards->sortBy('name') as $ward)
                    <div class="card stack">
                        <div class="row row-between">
                            <h3>{{ $ward->name }}</h3>
                            <form method="POST" action="{{ route('admin.wards.update', $ward) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="is_active" value="{{ $ward->is_active ? 0 : 1 }}">
                                <button type="submit" class="btn btn-quiet btn-small">{{ $ward->is_active ? __('admin.wards.deactivate') : __('admin.wards.activate') }}</button>
                            </form>
                        </div>
                        <p class="small muted">{{ $ward->is_active ? __('admin.wards.active') : __('admin.wards.inactive') }}</p>
                        <p class="small">
                            <strong>{{ __('admin.wards.areas') }}:</strong>
                            {{ $ward->areas->isEmpty() ? __('admin.wards.no_areas') : $ward->areas->pluck('name')->implode(', ') }}
                        </p>
                        <form method="POST" action="{{ route('admin.wards.areas.store', $ward) }}" class="row">
                            @csrf
                            <label for="area-{{ $ward->id }}" class="visually-hidden">{{ __('admin.wards.new_area') }}</label>
                            <input id="area-{{ $ward->id }}" name="name" type="text" maxlength="80" placeholder="{{ __('admin.wards.new_area') }}" required>
                            <button type="submit" class="btn btn-quiet">{{ __('admin.wards.add_area') }}</button>
                        </form>
                    </div>
                @endforeach
            </section>
        @endforeach
    @endforeach
    <x-field-error name="name" />
</x-layout.staff>
