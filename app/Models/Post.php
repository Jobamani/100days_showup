<?php

namespace App\Models;

use App\Models\Comment;
use App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'title', 'slug', 'body', 'image'];

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

        public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }
}
