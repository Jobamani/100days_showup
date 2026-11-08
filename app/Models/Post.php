<?php

namespace App\Models;

use App\Models\Comment;
use App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;

class Post extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'title', 'slug', 'body', 'image', 'featured','published'];

    // public function comments()
    // {
    //     return $this->hasMany(Comment::class);
    // }

    protected function excerpt(): Attribute
    {
        return Attribute::make(
            get: fn () => \Illuminate\Support\Str::limit($this->body, 100),
        );
    }

    protected static function booted(): void
    {
        static::updating(function (Post $post) {
            if ($post->isDirty('body')) {
                $post->summary = null;
            }
        });
    }

    protected function title(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => ucwords(trim($value)),
        );
    }

    protected function casts(): array
    {
        return [
            'featured' => 'boolean',
        ];
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

        public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

   public function scopePublished(Builder $query): Builder
    {
        return $query->where('published', true);
    }

    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
