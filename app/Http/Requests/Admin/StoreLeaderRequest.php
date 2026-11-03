<?php

namespace App\Http\Requests\Admin;

class StoreLeaderRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'office_id' => ['required', 'integer', 'exists:offices,id'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190', 'unique:users,email'],
            'term_started_on' => ['nullable', 'date'],
        ];
    }
}
