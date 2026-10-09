<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Requests\StorePostRequest;


class PostController extends Controller
{
    public function create()
    {
        return view('posts.create');
    }

    public function store(StorePostRequest $request)
    {
        // $data = $request->only(['title', 'body']);
        // $validate= $request->validate([
        //     'title'=> 'required|string|max:255',
        //     'body'=> 'required|string|min:10',
        // ]);
        $validated = $request->validated();
         if ($request->hasFile('image')) {
            $path = $request->file('image')->store('posts', 'public');
            $validated['image'] = $path;
        }

        $validated['user_id'] = 1; // hardcoded until auth exists, Week 5
        $validated['slug'] = Str::slug($validated['title']) . '-' . uniqid();

        // return view('posts.preview', ['data' => $validated]);
        $post =Post::create($validated);
        // session(['last_post' => $validated]);

        return redirect()->route('posts.show', $post->id)
            ->with('success', 'Post created successfully!');

    }

    public function show($id)
    {
        $post = Post::findOrFail($id);
        return view('posts.show', ['post' => $post]);
    }
}
