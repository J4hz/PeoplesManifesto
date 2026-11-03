<?php

namespace App\Http\Requests\Admin;

class ToggleUserActiveRequest extends AdminRequest
{
    public function authorize(): bool
    {
        // An admin cannot suspend their own account and lock everyone out.
        return parent::authorize() && $this->route('user')->id !== $this->user('web')->id;
    }

    public function rules(): array
    {
        return ['is_active' => ['required', 'boolean']];
    }
}
