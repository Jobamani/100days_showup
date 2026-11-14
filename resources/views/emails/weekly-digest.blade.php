<x-mail::message>
# Hi {{ $user->name }},

Here are the top posts from the last 7 days.

@foreach ($posts as $post)
## {{ $post->title }}

{{ $post->excerpt }}

<x-mail::button :url="url('/posts/'.$post->id)">
Read post
</x-mail::button>

@endforeach

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>