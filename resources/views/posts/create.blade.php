@extends('layouts.app')

@section('title', 'Create Post')

@section('content')
<div class="mx-auto max-w-2xl px-4 py-8">
    <x-card>
        <h1 class="font-display text-2xl font-bold text-ink">Create Post</h1>

        @if ($errors->any())
            <div class="mt-4 rounded-md border border-red-300 bg-red-50 p-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300">
                <ul class="list-disc ps-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('last_post'))
            <div class="mt-4 rounded-md bg-page p-3 text-sm text-muted">
                <strong class="text-ink">Last submitted:</strong> {{ session('last_post')['title'] }}
            </div>
        @endif

        <form action="{{ route('posts.store') }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-5">
            @csrf

            <div>
                <x-input-label for="title" value="Title" />
                <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title')" />
                <x-input-error :messages="$errors->get('title')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="body" value="Body" />
                <x-textarea id="body" name="body" rows="8" class="mt-1 block w-full">{{ old('body') }}</x-textarea>
                <x-input-error :messages="$errors->get('body')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="image" value="Featured Image" />
                <input id="image" name="image" type="file" class="mt-1 block w-full text-sm text-muted file:mr-3 file:rounded-md file:border-0 file:bg-page file:px-3 file:py-2 file:text-ink">
                <x-input-error :messages="$errors->get('image')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="tags" value="Tags" />
                <x-text-input id="tags" name="tags" type="text" class="mt-1 block w-full" :value="old('tags')" placeholder="laravel, php, tutorial" />
            </div>

            <x-primary-button>Publish</x-primary-button>
        </form>
    </x-card>
</div>
@endsection