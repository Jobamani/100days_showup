@props(['comments', 'action'])

<div class="mt-4 space-y-1 border-t border-line pt-3 text-sm" data-comments>
    @foreach ($comments as $comment)
        <div class="text-ink" data-comment-id="{{ $comment->id }}">
            {{ $comment->body }}
            <span class="text-muted">— {{ $comment->user->name }} · {{ $comment->created_at->diffForHumans() }}</span>
            @if (auth()->id() === $comment->user_id)
                <form method="POST" action="{{ route('comments.destroy', $comment) }}" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-muted hover:text-accent">delete</button>
                </form>
            @endif
        </div>
    @endforeach

    @auth
        <details class="pt-1">
            <summary class="cursor-pointer text-muted hover:text-accent">Add a comment</summary>
            <form method="POST" action="{{ $action }}" class="mt-2 space-y-2">
                @csrf
                <x-textarea name="comment" rows="2" class="block w-full"></x-textarea>
                <x-primary-button>Comment</x-primary-button>
            </form>
        </details>
    @endauth
</div>