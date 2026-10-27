<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Tag;
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

        $validated['user_id'] = auth()->id(); // hardcoded until auth exists, Week 5
        $validated['slug'] = Str::slug($validated['title']) . '-' . uniqid();

        // return view('posts.preview', ['data' => $validated]);
        $post =Post::create($validated);
        // session(['last_post' => $validated]);

        if ($request->filled('tags')) {
            $tagNames = array_map('trim', explode(',', $request->input('tags')));
            $tagIds = [];

            foreach ($tagNames as $name) {
                $tag = Tag::firstOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => $name]
                );
                $tagIds[] = $tag->id;
            }

            $post->tags()->attach($tagIds);
        }

        return redirect()->route('posts.show', $post->id)
            ->with('success', 'Post created successfully!');

    }

    public function show($id)
    {
        $post = Post::with('comments.user')->findOrFail($id);
        // $post = Post::findOrFail($id); n+1 testing
        return view('posts.show', ['post' => $post]);
    }

    public function edit($id)
    {
        $post = Post::findOrFail($id);
        $this->authorize('update', $post);
        return view('posts.edit', ['post' => $post]);
    }

    public function update(StorePostRequest $request, $id)
    {
        $post = Post::findOrFail($id);
        $this->authorize('update', $post);
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('posts', 'public');
        }

        $post->update($validated);

        return redirect()->route('posts.show', $post->id)
           ->with('success', 'Post updated successfully');
    }

    public function storeComment(Request $request, $id)
    {
        $request->validate(['body' => 'required|min:3']);

        Post::findOrFail($id)->comments()->create([
            'user_id' => auth()->id(),
            'body' => $request->input('body'),
        ]);

        return back()->with('success', 'comment added!');
    }

    public function destroy($id)
    {
        $post = Post::findOrFail($id);
        $this->authorize('delete', $post);
        $post->delete();

        return redirect()->route('posts.create')
           ->with('success', 'Post deleted.');
    }
}
