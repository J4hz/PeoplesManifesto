<?php

namespace App\Ai\Contracts;

use App\Ai\IntakeResult;
use App\Models\Submission;

interface IntakeAnalyzer
{
    /**
     * @param  list<string>  $wardNames  wards the model may route to
     * @param  list<string>  $areaNames  areas inside the resident's ward
     *
     * @throws \App\Ai\InvalidAiOutput when the output cannot be trusted
     */
    public function analyse(Submission $submission, array $wardNames, array $areaNames): IntakeResult;
}
