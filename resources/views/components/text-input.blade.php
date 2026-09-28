@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'sipma-input border-slate-300 focus:border-blue-600 focus:ring-blue-600 rounded-lg']) }}>
