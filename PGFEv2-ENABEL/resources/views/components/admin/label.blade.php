@props([
    'for' => null,
    'required' => false,
])

<label @if($for) for="{{ $for }}" @endif {{ $attributes->merge(['class' => 'admin-label']) }}>
    {{ $slot }}
    @if($required)
        <span class="text-rose-600">*</span>
    @endif
</label>
