@php $supportDate = \Carbon\Carbon::parse($fetchSetting->supported_until) @endphp
@if ($supportDate->isPast())
    <span
        class="text-danger1">Your support has been expired on <b>{{ $supportDate->translatedFormat('d M, Y') }}</b></span>
    <br>
@else
    <span class="text-success1">Your support will expire on <b>{{ $supportDate->translatedFormat('d M, Y') }}</b></span>
    @if($supportDate->diffInDays() < 90)
        <div class="h-mt2 mt-2">
            <p class="t-body -size-m -color-mid">
                <a class="img-lightbox"
                   data-image-url="{{ asset('img/Support_Extension_Cost.jpg') }}" href="javascript:;">How much do I save
                    by
                    extending now?</a>
            </p>
        </div>
    @endif
@endif



