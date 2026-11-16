<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-semibold text-ink leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-card class="flex items-center gap-4">
                @if (auth()->user()->avatar)
                    <img src="{{ asset('storage/' . auth()->user()->avatar) }}" alt="" class="h-16 w-16 rounded-full object-cover">
                @endif
                <div>
                    <p class="font-semibold text-ink">{{ auth()->user()->name }}</p>
                    @if (auth()->user()->bio)
                        <p class="text-sm text-muted">{{ auth()->user()->bio }}</p>
                    @endif
                </div>
            </x-card>

            @if ($lastPost)
                <x-card>
                    <a href="{{ route('posts.show', $lastPost->id) }}" class="font-semibold text-accent hover:underline">
                        Continue reading: {{ $lastPost->title }}
                    </a>
                </x-card>
            @endif
        </div>
    </div>
</x-app-layout>