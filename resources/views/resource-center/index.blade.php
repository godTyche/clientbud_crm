@extends('layouts.app')

@section('filter-section')

@endsection

@php
$addResourcePermission = user()->permission('add_resourcecenter');
@endphp

@section('content')
    <!-- CONTENT WRAPPER START -->
    <div class="content-wrapper">
        <!-- Add Task Export Buttons Start -->
        {{-- <input type="hidden" name="user_id" class="user_id" value={{user()->id}}> --}}
        <div class="d-flex justify-content-between action-bar">
            <div id="table-actions" class="flex-grow-1 align-items-center">
                @if(in_array('admin', user_roles()) || $addResourcePermission == 'all')
                    <x-forms.link-primary :link="route('resource-center.create')" class="mr-3 openRightModal float-left"
                        icon="plus">
                        @lang('modules.resourceCenter.addResource')
                    </x-forms.link-primary>
                @endif
            </div>
            <div id="emptyCartBox">
                <a href="javascript:;" class="f-20 mt-2 text-lightest d-flex align-items-center mr-3 empty-cart fa fa-trash" data-user-id = {{ user()->id }} data-toggle="tooltip" data-original-title="@lang('app.emptyCart')" ><i
                    ></i></a>
            </div>

            <!-- @if (!in_array('client', user_roles())) -->
                <x-datatable.actions>
                    <div class="select-status mr-3 pl-3">
                        <select name="action_type" class="form-control select-picker" id="quick-action-type" disabled>
                            <option value="">@lang('app.selectAction')</option>
                            {{-- <option value="change-status">@lang('modules.tasks.changeStatus')</option> --}}
                            <option value="change-purchase">@lang('app.purchaseAllow')</option>

                            <option value="delete">@lang('app.delete')</option>
                        </select>
                    </div>
                    <div class="select-status mr-3 d-none quick-action-field" id="change-status-action">
                        <select name="status" class="form-control select-picker">
                            <option value="1">@lang('app.allowed')</option>
                            <option value="0">@lang('app.notAllowed')</option>
                        </select>
                    </div>
                </x-datatable.actions>
            <!-- @endif -->
            
        </div>
        <div class="row p-20">
            @foreach ($resourceCenters as $item)
                <?php 
                    $employeesAssignee = explode(',', $item->employees);
                    $clientsAssignee = explode(',', $item->clients);
                ?>
                @if(in_array('admin', user()->roles->pluck('name')->toArray()) || in_array(user()->id, $employeesAssignee) || in_array(user()->id, $clientsAssignee))
                    <div class="col-xl-2 col-lg-3 col-md-4">
                        <x-cards.resource-card :resourceCard="$item" />
                    </div>
                @endif
            @endforeach
        </div>

    </div>
    <!-- CONTENT WRAPPER END -->

    <a class="btn btn-primary rounded f-14 p-2 mr-3 float-left back" style="display:none;position:absolute;">
        Back
    </a>
    <iframe src="" style="width: 100%; height: 100vh; display: none">
    </iframe>
@endsection

@push('scripts')
    <script>
        const showTable = () => {
            window.LaravelDataTables["email-marketing-table"].draw(false);
        }

        $('.back').click(function() {
            $('.content-wrapper').show();
            $('iframe').hide();
            $('.back').hide();
        })

        $('.resource-link').click(function(e) {
            e.preventDefault();
            link = $(this).attr('href');
            $('iframe').attr('src', link);
            $('.content-wrapper').hide();
            $('iframe').show();
            $('.back').show();
        });

        $('body').on('click', '.delete-resource', function() {
            var id = $(this).data('resource-id');
            Swal.fire({
                title: "@lang('messages.sweetAlertTitle')",
                text: "@lang('messages.recoverRecord')",
                icon: 'warning',
                showCancelButton: true,
                focusConfirm: false,
                confirmButtonText: "@lang('messages.confirmDelete')",
                cancelButtonText: "@lang('app.cancel')",
                customClass: {
                    confirmButton: 'btn btn-primary mr-3',
                    cancelButton: 'btn btn-secondary'
                },
                showClass: {
                    popup: 'swal2-noanimation',
                    backdrop: 'swal2-noanimation'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    var url = "{{ route('resource-center.destroy', ':id') }}";
                    url = url.replace(':id', id);

                    var token = "{{ csrf_token() }}";

                    $.easyAjax({
                        type: 'POST',
                        url: url,
                        data: {
                            '_token': token,
                            '_method': 'DELETE'
                        },
                        success: function(response) {
                            if (response.status == "success") {
                                window.location.reload();
                            }
                        }
                    });
                }
            });
        });
    </script>
@endpush
