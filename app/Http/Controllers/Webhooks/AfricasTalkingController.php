<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Http\Requests\Webhooks\InboundSmsRequest;
use App\Jobs\SendSms;
use App\Sms\SmsConversation;
use App\Support\PhoneNumber;
use Illuminate\Http\Response;

class AfricasTalkingController extends Controller
{
    public function sms(InboundSmsRequest $request, SmsConversation $conversation): Response
    {
        $phone = PhoneNumber::normalise($request->validated('from'));

        // Non-Kenyan numbers and short codes get no reply, so nothing is spent on them.
        if ($phone !== null) {
            $reply = $conversation->handle($phone, (string) $request->validated('text'));

            if ($reply !== null) {
                SendSms::dispatch($phone, $reply);
            }
        }

        return response('', 200);
    }
}
