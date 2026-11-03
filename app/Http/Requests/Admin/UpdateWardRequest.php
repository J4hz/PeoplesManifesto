<?php

namespace App\Http\Requests\Admin;

class UpdateWardRequest extends AdminRequest
{
    public function rules(): array
    {
        return ['is_active' => ['required', 'boolean']];
    }
}
