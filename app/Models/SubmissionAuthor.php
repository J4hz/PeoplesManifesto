<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionAuthor extends Model
{
    public const UPDATED_AT = null;

    protected $primaryKey = 'submission_id';

    public $incrementing = false;

    protected $fillable = ['submission_id', 'citizen_ref', 'citizen_id'];

    protected $hidden = ['citizen_id', 'citizen_ref'];

    protected function casts(): array
    {
        return ['citizen_id' => 'encrypted'];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
