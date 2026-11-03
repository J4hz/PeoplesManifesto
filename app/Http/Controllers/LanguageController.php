<?php

namespace App\Http\Controllers;

use App\Http\Requests\SetLanguageRequest;
use Illuminate\Http\RedirectResponse;

class LanguageController extends Controller
{
    public function __invoke(SetLanguageRequest $request): RedirectResponse
    {
        $locale = $request->string('locale')->toString();
        $request->session()->put('locale', $locale);
        $request->user('citizen')?->forceFill(['locale' => $locale])->save();

        return back();
    }
}
