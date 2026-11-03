<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAreaRequest;
use App\Http\Requests\Admin\UpdateWardRequest;
use App\Models\County;
use App\Models\Ward;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WardController extends Controller
{
    public function index(): View
    {
        return view('admin.wards', [
            'counties' => County::with(['constituencies.wards.areas'])->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateWardRequest $request, Ward $ward, AuditLogger $audit): RedirectResponse
    {
        $before = $ward->only('is_active');
        $ward->update(['is_active' => $request->boolean('is_active')]);
        $audit->record('ward.updated', $ward, $before, $ward->only('is_active'));

        return back()->with('status', __('admin.wards.saved', ['ward' => $ward->name]));
    }

    public function storeArea(StoreAreaRequest $request, Ward $ward, AuditLogger $audit): RedirectResponse
    {
        $area = $ward->areas()->create(['name' => trim($request->validated('name'))]);
        $audit->record('area.created', $area, after: $area->only(['ward_id', 'name']));

        return back()->with('status', __('admin.wards.area_added', ['area' => $area->name, 'ward' => $ward->name]));
    }
}
