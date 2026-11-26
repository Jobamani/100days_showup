<?php

namespace App\Models\Concerns;

use App\Models\Vote;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasVotes
{
    public function votes(): MorphMany
    {
        return $this->morphMany(Vote::class, 'votable');
    }
}