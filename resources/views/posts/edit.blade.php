@extends('layouts.app')

@section('title', 'EDit POst')

@section('content')
   <h1>Edit Post</h1>

   <form action = "{{ route('posts.update', $post->id) }}" method="POST"  enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div>
            <label for="title">Title</label>
            <input type="text" id="title" name="title" value="{{ old('title', $post->title) }}">
        </div>    
        
        <div>
            <label for=""image>Featured Image</label>
            <input type="file" id="image" name="image">
        </div>

        <button type="submit">Update</button>
    </form>
@endsection