@switch($resourceCard->colour)
    @case('green')
        @php
            $colour = 'dark-green';
        @endphp
    @break
    @case('purple')
        @php
            $colour = 'dark-grey';
        @endphp
    @break
    @default
        @php
        $colour = $resourceCard->colour;
        @endphp

@endswitch
<div class="card sticky-note border" style="margin: 5px">
    <div class="card-body text-justify text-wrap">
        <div class="row">

            <div class="col-10" style="text-align:center; padding:0">
                <h3 style="text-align:left">{{$resourceCard->title}}</h3>
                <a href="{{$resourceCard->url}}" target="_blank">{!! nl2br(urldecode($resourceCard->icon)) !!}</a>
            </div>
            <div class="col-2 text-right" style=" padding:0">
                <div class="dropdown">
                    <button class="btn btn-lg f-14 px-2 py-0 text-dark-grey text-capitalize rounded  dropdown-toggle"
                        type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-ellipsis-h"></i>
                    </button>

                    <div class="dropdown-menu dropdown-menu-right border-grey rounded b-shadow-4 p-0"
                        aria-labelledby="dropdownMenuLink" tabindex="0">
                        <a class="dropdown-item" target="_blank" 
                            href="{{ $resourceCard->url }}"><i
                                class="fa fa-eye mr-2"></i>@lang('app.view')</a>
                        <a class="openRightModal dropdown-item"
                            href="{{ route('resource-center.edit', $resourceCard->id) }}"><i
                                class="fa fa-edit mr-2"></i>@lang('app.edit')</a>
                        <a class="dropdown-item delete-resource" data-resource-id="{{ $resourceCard->id }}"
                            href="javascript:;"><i class="fa fa-trash mr-2"></i>@lang('app.delete')</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer bg-white border-0">
        <div class="d-flex justify-content-between">
            <div class="text-lightest">{{ $resourceCard->created_at->translatedFormat(company()->date_format) }}</div>
            <div class="text-{{ $colour }}"><i class="fa fa-circle"></i></div>
        </div>
    </div>
</div>
