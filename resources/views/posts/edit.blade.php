@extends('layouts.app')

@section('title', 'Edit Post')

@section('content')
<div class="mx-auto max-w-2xl px-4 py-8">
    <x-card>
        <h1 class="font-display text-2xl font-bold text-ink">Edit Post</h1>

        <form action="{{ route('posts.update', $post->id) }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-5">
            @csrf
            @method('PUT')

            <div>
                <x-input-label for="title" value="Title" />
                <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $post->title)" />
                <x-input-error :messages="$errors->get('title')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="image" value="Featured Image" />
                <input id="image" name="image" type="file" class="mt-1 block w-full text-sm text-muted file:mr-3 file:rounded-md file:border-0 file:bg-page file:px-3 file:py-2 file:text-ink">
            </div>

            <x-primary-button>Update</x-primary-button>
        </form>
    </x-card>
</div>
@endsection