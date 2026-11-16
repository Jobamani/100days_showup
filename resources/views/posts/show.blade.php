@extends('layouts.app')

@section('title', $post->title)

@section('content')
<div class="mx-auto max-w-3xl space-y-6 px-4 py-8">
    <x-card>
        <h1 class="font-display text-2xl font-bold text-ink">{{ $post->title }}</h1>

        @if ($post->tags->isNotEmpty())
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($post->tags as $tag)
                    <x-tag-pill :tag="$tag" />
                @endforeach
            </div>
        @endif

        @if ($post->image)
            <img src="{{ asset('storage/' . $post->image) }}" alt="{{ $post->title }}" class="mt-4 w-full rounded-md border border-line">
        @endif

        <div class="mt-4 whitespace-pre-line text-ink">{{ $post->body }}</div>

        @if ($post->excerpt)
            <p class="mt-4 text-sm italic text-muted">{{ $post->excerpt }}</p>
        @endif

        <p class="mt-4 text-xs text-muted">Posted {{ $post->created_at->diffForHumans() }}</p>

        @auth
            <div class="mt-4 flex flex-wrap items-center gap-4 border-t border-line pt-4">
                <a href="{{ route('posts.create') }}" class="text-sm font-medium text-accent hover:underline">Create another</a>

                @can('update', $post)
                    <a href="{{ route('posts.edit', $post->id) }}" class="text-sm font-medium text-accent hover:underline">Edit</a>
                @endcan

                @can('delete', $post)
                    <form action="{{ route('posts.destroy', $post->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this post?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm font-medium text-red-600 hover:underline dark:text-red-400">Delete</button>
                    </form>
                @endcan
            </div>
        @endauth
    </x-card>

    <x-card>
        <h2 class="font-semibold text-ink">Comments ({{ $post->comments->count() }})</h2>

        <div class="mt-3 divide-y divide-line">
            @forelse ($post->comments as $comment)
                <div class="py-3">
                    <p class="text-sm font-semibold text-ink">{{ $comment->user->name }}</p>
                    <p class="mt-1 text-sm text-ink">{{ $comment->body }}</p>
                </div>
            @empty
                <p class="py-3 text-sm text-muted">No comments yet.</p>
            @endforelse
        </div>

        @auth
            <form action="{{ route('comments.store', $post->id) }}" method="POST" class="mt-4 space-y-3">
                @csrf
                <x-textarea name="body" rows="3" class="block w-full" placeholder="Add a comment..."></x-textarea>
                <x-primary-button>Comment</x-primary-button>
            </form>
        @else
            <p class="mt-4 text-sm text-muted"><a href="{{ route('login') }}" class="text-accent hover:underline">Log in</a> to comment.</p>
        @endauth
    </x-card>
</div>
@endsection