@extends('layouts.app')

@section('title', 'Create Post')

@section('content')
    <h1>Create Post</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('posts.store') }}" method="POST">
        @csrf

        <div>
            <label for="title">Title</label>
            <input type="text" id="title" name="title" value="{{ old('title') }}">
             @error('title')
                <div style="color: red;">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label for="body">Body</label>
            <textarea id="body" name="body">{{ old('body') }}</textarea>
             @error('body')
                <div style="color: red;">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit">Publish</button>
    </form>
@endsection