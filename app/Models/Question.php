<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Models\Tag;
use App\Models\User;
use App\Models\Answer;
use App\Models\Concerns\HasVotes;


class Question extends Model
{
    use HasFactory, HasVotes;

    protected $fillable = ['title', 'body'];

    protected static function booted(): void
    {
        static::saving(function (Question $question) {
            if ($question->isDirty('title') || ! $question->slug) {
                $question->slug = Str::slug($question->title);
            }
        });

        static::deleting(function (Question $question) {
            // the database cascade removes the answers without firing events,
            // so their votes have to go first
            Vote::where('votable_type', (new Answer)->getMorphClass())
                ->whereIn('votable_id', $question->answers()->select('id'))
                ->delete();

            $question->votes()->delete();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }
}