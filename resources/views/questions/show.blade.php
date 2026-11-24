<x-app-layout>
    <div class="max-w-3xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
        @if (session('status'))
            <div class="rounded-lg border border-line bg-surface p-3 text-sm text-ink">{{ session('status') }}</div>
        @endif

        <x-card>
            <h1 class="text-2xl font-semibold text-ink">{{ $question->title }}</h1>
            <p class="mt-1 text-sm text-muted">
                asked by {{ $question->user->name }} · {{ $question->created_at->diffForHumans() }}
            </p>

            <div class="mt-4 text-ink whitespace-pre-line">{{ $question->body }}</div>

            @if ($question->tags->isNotEmpty())
                <div class="mt-4 flex flex-wrap gap-2">
                   @foreach ($question->tags as $tag)
                        <x-tag-pill :tag="$tag" />
                   @endforeach
                </div>
            @endif
        </x-card>

        <h2 class="text-lg font-semibold text-ink">
            {{ $question->answers->count() }} {{ Str::plural('Answer', $question->answers->count()) }}
        </h2>

        @foreach ($question->answers as $answer)
            <x-card id="answer-{{ $answer->id }}">
                <div class="text-ink whitespace-pre-line">{{ $answer->body }}</div>
                <p class="mt-3 text-sm text-muted">
                    answered by {{ $answer->user->name }} · {{ $answer->created_at->diffForHumans() }}
                </p>
            </x-card>
        @endforeach

        @auth
            <x-card>
                <form method="POST" action="{{ route('questions.answers.store', $question) }}" class="space-y-3">
                    @csrf
                    <x-input-label for="body" value="Your answer" />
                    {{-- use x-textarea exactly as posts/create.blade.php does --}}
                    <x-textarea id="body" name="body" rows="6" class="w-full">{{ old('body') }}</x-textarea>
                    <x-input-error :messages="$errors->get('body')" />
                    <x-primary-button>Post answer</x-primary-button>
                </form>
            </x-card>
        @else
            <x-card>
                <p class="text-muted"><a href="{{ route('login') }}" class="text-accent underline">Log in</a> to answer this question.</p>
            </x-card>
        @endauth
    </div>
</x-app-layout>