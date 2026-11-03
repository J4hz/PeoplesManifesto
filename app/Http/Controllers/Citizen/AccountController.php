<?php

namespace App\Http\Controllers\Citizen;

use App\Actions\DeleteCitizenAccount;
use App\Http\Controllers\Controller;
use App\Http\Requests\Citizen\DeleteAccountRequest;
use App\Http\Requests\Citizen\UpdateAccountRequest;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function edit(Request $request): View
    {
        $citizen = $request->user('citizen')->load('ward');

        return view('citizen.account', [
            'citizen' => $citizen,
            'maskedPhone' => PhoneNumber::mask($citizen->phone),
        ]);
    }

    public function update(UpdateAccountRequest $request): RedirectResponse
    {
        $citizen = $request->user('citizen');
        $citizen->forceFill([
            'locale' => $request->string('locale')->toString(),
            'sms_notifications' => $request->boolean('sms_notifications'),
        ])->save();

        $request->session()->put('locale', $citizen->locale);

        return back()->with('status', __('citizen.account.saved', [], $citizen->locale));
    }

    public function destroy(DeleteAccountRequest $request, DeleteCitizenAccount $delete): RedirectResponse
    {
        $citizen = $request->user('citizen');

        Auth::guard('citizen')->logout();
        $delete->handle($citizen);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', __('citizen.account.deleted'));
    }
}
