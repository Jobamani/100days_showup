@extends('layouts.app')

@section('title', 'Create Post')

@section('content')
    <h1>Create Post</h1>

    <form action="{{ route('posts.store') }}" method="POST">
        @csrf

        <div>
            <label for="title">Title</label>
            <input type="text" id="title" name="title" value="{{ old('title') }}">
        </div>

        <div>
            <label for="body">Body</label>
            <textarea id="body" name="body">{{ old('body') }}</textarea>
        </div>

        <button type="submit">Publish</button>
    </form>
@endsection