<li {{ $isActive($menu) ? 'class=active' : '' }} {{ $attributes }} >
    <a class="d-block f-15 text-dark-grey text-capitalize border-bottom-grey" href="{{ $href }}">{{ $text }}</a>
</li>
