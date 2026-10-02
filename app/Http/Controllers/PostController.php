<?php

namespace App\Http\Controllers;

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

        // return view('posts.preview', ['data' => $validated]);
        session(['last_post' => $validated]);

        return redirect()->route('posts.create')
            ->with('success', 'Post created successfully!');

    }

    public function show($id)
    {
        return "Post $id";
    }
}
