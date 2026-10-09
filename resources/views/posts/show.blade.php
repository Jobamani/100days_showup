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

@endsection    
