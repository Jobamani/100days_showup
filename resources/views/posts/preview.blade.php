@extends('layouts.app')

@section('title', 'Submitted Data')

@section('content')
    <h1>You submitted:</h1>
    <p><strong>Title:</strong> {{ $data['title'] }}</p>
    <p><strong>Body:</strong> {{ $data['body'] }}</p>
    <a href="{{ route('posts.create') }}">Create another</a>
@endsection