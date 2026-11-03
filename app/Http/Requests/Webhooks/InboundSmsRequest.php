<?php

namespace App\Http\Requests\Webhooks;

use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Africa's Talking does not sign its callbacks. The request is trusted only
 * if the secret token in the URL matches, and, when configured, it comes from
 * one of Africa's Talking's published addresses.
 */
class InboundSmsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $expected = (string) config('services.africastalking.webhook_token');
        $given = (string) $this->route('token');

        if (strlen($expected) < 24 || ! hash_equals($expected, $given)) {
            return false;
        }

        $ips = config('services.africastalking.webhook_ips');

        return $ips === [] || IpUtils::checkIp((string) $this->ip(), $ips);
    }

    public function rules(): array
    {
        return [
            'from' => ['required', 'string', 'max:20'],
            'text' => ['nullable', 'string', 'max:1000'],
            'to' => ['nullable', 'string', 'max:20'],
            'id' => ['nullable', 'string', 'max:100'],
            'date' => ['nullable', 'string', 'max:40'],
            'linkId' => ['nullable', 'string', 'max:100'],
        ];
    }
}
