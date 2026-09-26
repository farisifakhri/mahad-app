<button {{ $attributes->merge(['type' => 'submit', 'class' => 'sipma-button focus:ring-2 focus:ring-blue-500 focus:ring-offset-2']) }}>
    {{ $slot }}
</button>
