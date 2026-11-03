<?php

namespace App\Actions;

use App\Models\Citizen;
use App\Models\OtpCode;
use App\Models\SmsSession;
use App\Models\SubmissionAuthor;
use Illuminate\Support\Facades\DB;

/**
 * Right to erasure under the Data Protection Act. The phone number and the
 * encrypted link to the person go. The anonymous report and the keyed
 * reference stay, so the ward's counts and history do not change.
 */
class DeleteCitizenAccount
{
    public function handle(Citizen $citizen): void
    {
        DB::transaction(function () use ($citizen) {
            SubmissionAuthor::where('citizen_ref', $citizen->ref())->update(['citizen_id' => null]);
            OtpCode::where('phone_hash', $citizen->phone_hash)->delete();
            SmsSession::where('phone_hash', $citizen->phone_hash)->delete();
            $citizen->notifications()->delete();
            $citizen->delete();
        });
    }
}
