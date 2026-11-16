@extends('layouts.app')

@section('title', 'Submitted Data')

@section('content')
<div class="mx-auto max-w-2xl px-4 py-8">
    <x-card>
        <h1 class="font-display text-2xl font-bold text-ink">You submitted:</h1>
        <p class="mt-3 text-ink"><strong>Title:</strong> {{ $data['title'] }}</p>
        <p class="mt-1 text-ink"><strong>Body:</strong> {{ $data['body'] }}</p>
        @if (!empty($data['image']))
            <img src="{{ asset('storage/' . $data['image']) }}" alt="Featured image" class="mt-4 max-w-sm rounded-md border border-line">
        @endif
        <a href="{{ route('posts.create') }}" class="mt-4 inline-block text-sm font-medium text-accent hover:underline">Create another</a>
    </x-card>
</div>
@endsection