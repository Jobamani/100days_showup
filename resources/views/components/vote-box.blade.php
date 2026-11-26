@props(['kind', 'score', 'action', 'mine' => null, 'canVote' => false])

<div class="flex shrink-0 flex-col items-center gap-1" @if ($mine !== null) data-my-vote="{{ $mine }}" @endif>
    @if ($canVote)
        <form action="{{ $action }}" method="POST">
            @csrf
            <input type="hidden" name="value" value="1">
            <button type="submit" aria-label="Upvote" aria-pressed="{{ $mine === 1 ? 'true' : 'false' }}"
                    class="rounded p-1 hover:bg-page {{ $mine === 1 ? 'text-accent' : 'text-muted' }}">▲</button>
        </form>
    @endif

    <span class="text-lg font-semibold text-ink" data-{{ $kind }}-score="{{ $score }}">{{ $score }}</span>

    @if ($canVote)
        <form action="{{ $action }}" method="POST">
            @csrf
            <input type="hidden" name="value" value="-1">
            <button type="submit" aria-label="Downvote" aria-pressed="{{ $mine === -1 ? 'true' : 'false' }}"
                    class="rounded p-1 hover:bg-page {{ $mine === -1 ? 'text-accent' : 'text-muted' }}">▼</button>
        </form>
    @endif
</div>