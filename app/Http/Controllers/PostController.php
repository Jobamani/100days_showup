<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


class PostController extends Controller
{
    public function create()
    {
        return view('posts.create');
    }

    public function store(Request $request)
    {
        // $data = $request->only(['title', 'body']);
        // $validate= $request->validate([
        //     'title'=> 'required|string|max:255',
        //     'body'=> 'required|string|min:10',
        // ]);
        $validated = $request->validated();

        return view('posts.preview', ['data' => $validated]);

    }

    public function show($id)
    {
        return "Post $id";
    }
}
