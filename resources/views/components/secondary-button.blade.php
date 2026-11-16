<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2 bg-surface border border-line rounded-md font-semibold text-sm text-ink shadow-sm hover:bg-page focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 focus:ring-offset-surface disabled:opacity-50 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>