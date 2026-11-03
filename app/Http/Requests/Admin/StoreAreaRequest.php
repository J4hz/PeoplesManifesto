<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class StoreAreaRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80', Rule::unique('areas', 'name')->where('ward_id', $this->route('ward')->id)],
        ];
    }
}
