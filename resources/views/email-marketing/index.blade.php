@extends('layouts.app')

@push('datatable-styles')
    @include('sections.datatable_css')
@endpush

@section('filter-section')

@endsection

@php
$addProductPermission = user()->permission('add_product');
$addOrderPermission = user()->permission('add_order');
@endphp

@section('content')
    <!-- CONTENT WRAPPER START -->
    <div class="content-wrapper">
        <!-- Add Task Export Buttons Start -->
        {{-- <input type="hidden" name="user_id" class="user_id" value={{user()->id}}> --}}
        <div class="d-flex justify-content-between action-bar">
            <div id="table-actions" class="flex-grow-1 align-items-center">
                <!-- @if ($addProductPermission == 'all' || $addProductPermission == 'added') -->
                    <x-forms.link-primary :link="route('email-marketing.create')" class="mr-3 openRightModal float-left"
                        icon="plus">
                        @lang('modules.emailMarketing.addEmail')
                    </x-forms.link-primary>
                <!-- @endif -->
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

        <!-- Add Task Export Buttons End -->
        <!-- Task Box Start -->
        <div class="d-flex flex-column w-tables rounded mt-3 bg-white table-responsive">

            {!! $dataTable->table(['class' => 'table table-hover border-0 w-100']) !!}

        </div>
        <!-- Task Box End -->
    </div>
    <!-- CONTENT WRAPPER END -->

@endsection

@push('scripts')
    @include('sections.datatable_js')
    <script src="https://cdn.ckeditor.com/ckeditor5/41.1.0/super-build/ckeditor.js"></script>
    <script>
        const showTable = () => {
            window.LaravelDataTables["email-marketing-table"].draw(false);
        }

        $('body').on('click', '.delete-table-row', function() {
            var id = $(this).data('product-id');
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
                    var url = "{{ route('email-marketing.destroy', ':id') }}";
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
                                showTable();
                            }
                        }
                    });
                }
            });
        });
    </script>
@endpush
