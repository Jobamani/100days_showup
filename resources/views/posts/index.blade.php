@extends('layouts.app')

@section('title', 'Home')

@section('content')
<div class="mx-auto max-w-3xl space-y-4 px-4 py-8">
    @forelse ($posts as $post)
        <x-card>
            <a href="{{ route('posts.show', $post->id) }}" class="font-display text-lg font-bold text-ink hover:text-accent">
                {{ $post->title }}
            </a>
            <p class="mt-1 text-sm text-muted">{{ $post->excerpt ?: Str::limit($post->body, 140) }}</p>

            <div class="mt-3 flex flex-wrap items-center gap-2">
                @foreach ($post->tags as $tag)
                    <x-tag-pill :tag="$tag" />
                @endforeach
                <span class="ms-auto text-xs text-muted">
                    by {{ $post->user->name }}
                    · {{ $post->comments_count }} {{ Str::plural('comment', $post->comments_count) }}
                    · {{ $post->created_at->diffForHumans() }}
                </span>
            </div>
        </x-card>
    @empty
        <x-card><p class="text-muted">No posts yet.</p></x-card>
    @endforelse

    {{ $posts->links() }}
</div>
@endsection