<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;


class PostController extends Controller
{
    use AuthorizesRequests;
    public function index()
    {
        return PostResource::collection(Post::published()->with('user')->latest()->paginate(10));
    }

    public function show(int $id)
    {
        return PostResource::make(Post::published()->with('user')->findOrFail($id));
    }

        public function store(StorePostRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('posts', 'public');
        }

        $data['user_id'] = $request->user()->id;   // never from client input
        $data['slug'] = Str::slug($data['title']).'-'.uniqid();

        $post = Post::create($data)->load('user');

        return PostResource::make($post)->response()->setStatusCode(201);
    }

    public function update(StorePostRequest $request, int $id)
    {
        $post = Post::findOrFail($id);          // not published() so owners can edit drafts
        $this->authorize('update', $post);

        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('posts', 'public');
        }

        $post->update($data);

        return PostResource::make($post->load('user'));
    }

    public function destroy(int $id)
    {
        $post = Post::findOrFail($id);
        $this->authorize('delete', $post);
        $post->delete();

        return response()->noContent();
    }
}
