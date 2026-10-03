@extends('errors::minimal')

@section('title', __('Not Found'))
@section('code', '404')
@section('message', __('Not Found'))


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Page Not Found</title>
    <style>
        body { font-family: system-ui, sans-serif; text-align: center; padding: 4rem 1rem; }
        h1 { font-size: 4rem; margin-bottom: 0; color: #d85a30; }
        p { font-size: 1.2rem; color: #666; }
        a { color: #d85a30; }
    </style>
</head>
<body>
    <h1>404</h1>
    <p>This page doesn't exist — it might have been moved, or the URL is wrong.</p>
    <a href="{{ url('/') }}">Back to home</a>
</body>
</html>