<?php

namespace App\Http\Controllers\Api;

use App\Models\Post;
use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;


class PostController extends Controller
{
    public function index()
    {
        return PostResource::collection(Post::published()->with('user')->latest()->paginate(10));
    }

    public function show(int $id)
    {
        return PostResource::make(Post::published()->with('user')->findOrFail($id));
    }
}
