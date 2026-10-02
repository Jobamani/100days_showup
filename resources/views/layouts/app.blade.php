<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'My Blog') | 100 Days of Laravel</title>

    <style>
        body { font-family: system-ui, sans-serif; max-width: 720px; margin: 0 auto; padding: 0 1rem; line-height: 1.6; }
        header, footer { padding: 1rem 0; border-bottom: 1px solid #ddd; }
        footer { border-bottom: 0; border-top: 1px solid #ddd; margin-top: 2rem; color: #666; }
        nav a { margin-right: 1rem; }
        label { display: block; margin-top: 1rem; font-weight: 600; }
        input[type="text"], textarea { width: 100%; padding: .5rem; }
        button { margin-top: 1rem; padding: .5rem 1rem; }
    </style>
</head>
<body>

    <header>
        <nav>
            <a href="/">Home</a>
            <a href="/about">About</a>
            <a href="{{ route('posts.create') }}">New Post</a>
        </nav>
    </header>

    <main>
        @if (session('success'))
            <div style="background: #d4edda; color: #155724; padding: 1rem; margin: 1rem 0;">
                {{ session('success') }}
            </div>
        @endif
        @yield('content')
    </main>

    <footer>
        <p>&copy; {{ date('Y') }} 100 Days of Laravel</p>
    </footer>

</body>
</html>