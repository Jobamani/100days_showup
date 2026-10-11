@extends ('layouts.app')

@section('title', $post->title)

@section('content')
    <h1>{{$post->title}}</h1>

    @if ($post->image)
         <img src="{{ asset('storage/' . $post->image)}}" alt="{{$post->title}}" style="max-width: 400px;">
    @endif     
    
    <p>{{$post->body}}</p>

    <p><small>Posted {{ $post->created_at->diffForHumans() }}</small></p>

    <a href="{{ route('posts.create')}}">Create another</a>

    <a href="{{ route('posts.edit', $post->$id )}}">Edit</a>

    <form action="{{ route('posts.destroy', $post->id) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('Delete this post?');">
        @csrf
        @method('DELETE')
        <button type="submit">Delete</button>
</form>    

@endsection    
