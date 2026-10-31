<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    @if (auth()->user()->avatar)
        <img src="{{ asset('storage/' . auth()->user()->avatar) }}" style="max-width: 150px; border-radius: 50%;">
    @endif

    <p>{{ auth()->user()->bio }}</p>

    @if ($lastPost)
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 pt-6">
            <a href="{{ route('posts.show', $lastPost->id) }}"
               class="text-indigo-600 dark:text-indigo-400 underline">
                Continue reading: {{ $lastPost->title }}
            </a>
        </div>
    @endif

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    {{ __("You're logged in!") }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>