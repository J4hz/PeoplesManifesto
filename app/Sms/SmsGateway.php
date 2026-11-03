<?php

namespace App\Sms;

interface SmsGateway
{
    /**
     * @param  string  $to  normalised +254 number
     */
    public function send(string $to, string $message): void;
}
