<?php

namespace App\Http\Requests\Leader;

use App\Models\IssueCluster;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('web')?->can('respond', $this->route('cluster')) ?? false;
    }

    public function rules(): array
    {
        /** @var IssueCluster $cluster */
        $cluster = $this->route('cluster');
        $statuses = config('civic.leader_statuses');

        // Staff can draft any update except one that closes the issue.
        if (! $this->user('web')->can('markDone', $cluster)) {
            $statuses = array_values(array_diff($statuses, [IssueCluster::STATUS_DONE]));
        }

        return [
            'status' => ['nullable', 'string', Rule::in($statuses)],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
            'expected_completion_on' => ['nullable', 'date', 'after_or_equal:today', 'before:+2 years'],
            'photo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('civic.photos.max_kilobytes')],
            'notify_reporters' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => __('leader.respond.status_not_allowed'),
        ];
    }
}
