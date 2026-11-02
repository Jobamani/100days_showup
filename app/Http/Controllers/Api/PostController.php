<?php

namespace App\Http\Controllers\Api;

use App\Models\Post;
use App\Http\Controllers\Controller;


class PostController extends Controller
{
    public function index()
    {
        return Post::published()->latest()->paginate(10);
    }

    public function show(int $id)
    {
        return Post::published()->findOrFail($id);
    }
}
