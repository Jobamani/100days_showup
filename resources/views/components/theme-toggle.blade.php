<div x-data="{
        theme: localStorage.getItem('theme') || 'system',
        set(t) { this.theme = t; localStorage.setItem('theme', t); this.apply(); },
        apply() {
            const dark = this.theme === 'dark' || (this.theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
        }
    }"
    x-init="window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => theme === 'system' && apply())"
    class="inline-flex rounded-md border border-white/20 text-xs" role="group" aria-label="Theme">
    @foreach (['light' => 'Light', 'dark' => 'Dark', 'system' => 'Auto'] as $value => $label)
        <button type="button" @click="set('{{ $value }}')"
            :aria-pressed="theme === '{{ $value }}'"
            :class="theme === '{{ $value }}' ? 'bg-white/20 text-white' : 'text-white/70 hover:text-white'"
            class="px-2 py-1 first:rounded-l-md last:rounded-r-md">{{ $label }}</button>
    @endforeach
</div>

@include('layouts.theme-script')