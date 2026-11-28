<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\Question;
use App\Models\Concerns\HasVotes;
use App\Models\Concerns\HasComments;




class Answer extends Model
{
    use HasFactory, HasVotes, HasComments;

    protected $fillable = ['body'];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (Answer $answer) {
            $answer->votes()->delete();
            $answer->comments()->delete();
        });
    }
}