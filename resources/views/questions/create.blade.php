@extends('layouts.app')

@section('title', 'Ask a Question')

@section('content')
<div class="mx-auto max-w-2xl px-4 py-8">
    <x-card>
        <h1 class="font-display text-2xl font-bold text-ink">Ask a Question</h1>

        <form action="{{ route('questions.store') }}" method="POST" class="mt-6 space-y-5">
            @csrf

            <div>
                <x-input-label for="title" value="Title" />
                <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title')" />
                <x-input-error :messages="$errors->get('title')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="body" value="Details (Markdown supported)" />
                <x-textarea id="body" name="body" rows="10" class="mt-1 block w-full">{{ old('body') }}</x-textarea>
                <x-input-error :messages="$errors->get('body')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="tags" value="Tags (1 to 5, separated by commas)" />
                <x-text-input id="tags" name="tags" type="text" class="mt-1 block w-full" :value="old('tags')" placeholder="laravel, caching" />
                <x-input-error :messages="$errors->get('tags')" class="mt-2" />
            </div>

            <x-primary-button>Post question</x-primary-button>
        </form>
    </x-card>
</div>
@endsection