<?php

namespace App\Models;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Events\CommentCreated;


class Comment extends Model
{
    use HasFactory;

    protected $fillable = ['commentable_id', 'commentable_type', 'user_id', 'body'];

    protected $dispatchesEvents = [
        'created' => CommentCreated::class,
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function commentable()
    {
        return $this->morphTo();
    }
}

