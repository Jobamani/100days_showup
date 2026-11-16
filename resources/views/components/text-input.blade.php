@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-line bg-surface text-ink placeholder:text-muted focus:border-accent focus:ring-accent rounded-md shadow-sm']) }}>