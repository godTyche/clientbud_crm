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

@php
$editResourcePermission = user()->permission('edit_resourcecenter');
$deleteResourcePermission = user()->permission('delete_resourcecenter');
@endphp

<div class="card resource-card sticky-note border" style="margin: 5px; border-radius: 24px; position: relative;">
    <div class="card-body text-justify text-wrap" style="padding: 0;">
        <div class="row" style=" margin: 0;">

            <div class="col-12" style="text-align:center; padding:0">
                <h4 class="resource-card-header" style="border-radius: 24px 24px 0 0; line-height: 36px; margin-bottom: 1rem">{{$resourceCard->title}}</h4>
                <a href="{{$resourceCard->url}}" class="resource-link">{!! nl2br(urldecode($resourceCard->icon)) !!}</a>
            </div>
            
        </div>
    </div>
    <div class="dropdown" style="position: absolute; top:0; right: 0;">
        <button class="btn btn-lg f-14 px-2 py-0 text-dark-grey text-capitalize rounded  dropdown-toggle"
            type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fa fa-ellipsis-h" style="color:white"></i>
        </button>

        <div class="dropdown-menu dropdown-menu-right border-grey rounded b-shadow-4 p-0"
            aria-labelledby="dropdownMenuLink" tabindex="0">
            <a class="dropdown-item" target="_blank" 
                href="{{ $resourceCard->url }}"><i
                    class="fa fa-eye mr-2"></i>@lang('app.view')</a>
            @if($editResourcePermission == 'all' || $editResourcePermission == 'added' || in_array('admin', user_roles()))
            <a class="openRightModal dropdown-item"
                href="{{ route('resource-center.edit', $resourceCard->id) }}"><i
                    class="fa fa-edit mr-2"></i>@lang('app.edit')</a>
            @endif
            @if($deleteResourcePermission == 'all' || $deleteResourcePermission == 'added' || in_array('admin', user_roles()))
            <a class="dropdown-item delete-resource" data-resource-id="{{ $resourceCard->id }}"
                href="javascript:;"><i class="fa fa-trash mr-2"></i>@lang('app.delete')</a>
            @endif
        </div>
    </div>

</div>
