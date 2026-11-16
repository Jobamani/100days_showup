@props(['tag'])

<span {{ $attributes->merge(['class' => 'inline-block rounded-full border border-line bg-page px-2.5 py-1 text-xs font-medium text-muted']) }}>
    {{ $tag->name }}
</span>