@extends('layouts.app')

@section('title', $question->title)

@section('content')
<div class="mx-auto max-w-3xl space-y-6 px-4 py-8">
    @if (session('status'))
        <div class="rounded-md border border-line bg-surface p-3 text-sm text-ink">{{ session('status') }}</div>
    @endif

    <x-card>
        <div class="flex gap-4">
            <x-vote-box kind="question"
                        :score="(int) $question->score"
                        :mine="$myVotes->get('question:'.$question->id)"
                        :action="route('questions.vote', $question)"
                        :can-vote="auth()->check() && auth()->id() !== $question->user_id" />

            <div class="min-w-0 flex-1">
                <h1 class="font-display text-2xl font-bold text-ink">{{ $question->title }}</h1>
                <p class="mt-1 text-sm text-muted">
                    asked by {{ $question->user->name }} · {{ $question->created_at->diffForHumans() }}
                </p>

                <div class="markdown mt-4 text-ink">{{ \App\Support\Markdown::render($question->body) }}</div>

                @if ($question->tags->isNotEmpty())
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ($question->tags as $tag)
                            <x-tag-pill :tag="$tag" />
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </x-card>

    <h2 class="font-display text-lg font-bold text-ink">
        {{ $question->answers->count() }} {{ Str::plural('Answer', $question->answers->count()) }}
    </h2>

    @foreach ($question->answers as $answer)
        <x-card id="answer-{{ $answer->id }}">
            <div class="flex gap-4">
                <x-vote-box kind="answer"
                            :score="(int) $answer->score"
                            :mine="$myVotes->get('answer:'.$answer->id)"
                            :action="route('answers.vote', $answer)"
                            :can-vote="auth()->check() && auth()->id() !== $answer->user_id" />

                <div class="min-w-0 flex-1">
                    <div class="markdown text-ink">{{ \App\Support\Markdown::render($answer->body) }}</div>
                    <p class="mt-3 text-sm text-muted">
                        answered by {{ $answer->user->name }} · {{ $answer->created_at->diffForHumans() }}
                    </p>
                </div>
            </div>
        </x-card>
    @endforeach

    @auth
        <x-card>
            <form action="{{ route('questions.answers.store', $question) }}" method="POST" class="space-y-3">
                @csrf
                <div>
                    <x-input-label for="body" value="Your answer (Markdown supported)" />
                    <x-textarea id="body" name="body" rows="6" class="mt-1 block w-full">{{ old('body') }}</x-textarea>
                    <x-input-error :messages="$errors->get('body')" class="mt-2" />
                </div>
                <x-primary-button>Post answer</x-primary-button>
            </form>
        </x-card>
    @else
        <x-card>
            <p class="text-muted">
                <a href="{{ route('login') }}" class="text-accent underline">Log in</a> to answer this question.
            </p>
        </x-card>
    @endauth
</div>
@endsection