@extends ('layouts.app')

@section('title', $post->title)

@section('content')
    <h1>{{$post->title}}</h1>

    @if ($post->tags->isNotEmpty())
        <p>
            @foreach ($post->tags as $tag)
                <span style="background:#eee; padding:2px 8px; border-radius:4px; margin-right:4px;">{{ $tag->name }}</span>
            @endforeach
        </p>
    @endif

    @if ($post->image)
         <img src="{{ asset('storage/' . $post->image)}}" alt="{{$post->title}}" style="max-width: 400px;">
    @endif     
    
    <p>{{$post->body}}</p>

    <p><em>{{ $post->excerpt }}</em></p>

    <p><small>Posted {{ $post->created_at->diffForHumans() }}</small></p>

    <a href="{{ route('posts.create')}}">Create another</a>

    <a href="{{ route('posts.edit', $post->id )}}">Edit</a>

    <form action="{{ route('posts.destroy', $post->id) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('Delete this post?');">
        @csrf
        @method('DELETE')
        <button type="submit">Delete</button>
    </form>    


    <h2>Comments ({{ $post->comments->count()}})</h2>

    @forelse ($post->comments as $comment)
        <div style="border-top: 1px solid #add; padding:0.5rem 0;">
            <strong>{{ $comment->user->name }}</strong>
            <p>{{ $comment->body }}</p>
        </div>
    @empty
        <p>No comments yet.</p>
    @endforelse

   <form action="{{ route('comments.store', $post->id) }}" method="POST">
        @csrf
        <textarea name="body" placeholder="Add a comment..."></textarea>        
        <button type="submit">Comment</button>
    </form>
@endsection    
