<?php

namespace App\Http\Requests\Admin;

class StoreStaffRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'leader_id' => ['required', 'integer', 'exists:leaders,id'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190', 'unique:users,email'],
        ];
    }
}
