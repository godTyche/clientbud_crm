<table {{ $attributes->merge(['class' => 'table', 'id' => 'example' ]) }}>
    @isset($thead)
        <thead class="{{ $headType }}">
            <tr>
                {!! $thead !!}
            </tr>
        </thead>
    @endisset
    <tbody>
        {{ $slot }}
    </tbody>
</table>
