@extends('layouts.app')

@push('datatable-styles')
    @include('sections.daterange_css')
@endpush

@push('styles')
    <!-- Drag and Drop CSS -->
    <link rel='stylesheet' href="{{ asset('vendor/css/dragula.css') }}" type='text/css' />
    <link rel='stylesheet' href="{{ asset('vendor/css/drag.css') }}" type='text/css' />
    <link rel="stylesheet" href="{{ asset('vendor/css/bootstrap-colorpicker.css') }}" />
    <style>
        #colorpicker .form-group {
            width: 87%;
        }

        .b-p-tasks {
            min-height: 90%;
        }

    </style>

@endpush

@php
$addLeadPermission = user()->permission('add_deals');
$viewLeadPermission = user()->permission('view_deals');
@endphp

@section('filter-section')

    @include('leads.filters')

@endsection


@section('content')

    <!-- CONTENT WRAPPER START -->
    <div class="w-task-board-box px-4 py-2 bg-white">
        <!-- Add Task Export Buttons Start -->
        <div class="d-grid d-lg-flex d-md-flex action-bar my-3">

            <x-alert type="warning" icon="info" class="d-lg-none">@lang('messages.dragDropScreenInfo')</x-alert>

            <div id="table-actions" class="flex-grow-1 align-items-center">
                @if ($addLeadPermission == 'all' || $addLeadPermission == 'added')
                    <x-forms.link-primary :link="route('deals.create')" class="mr-3 openRightModal float-left" icon="plus">
                        @lang('modules.deal.addDeal')
                    </x-forms.link-primary>
                @endif
                @if (user()->permission('manage_deal_stages') == 'all')
                    <x-forms.button-secondary icon="plus" id="add-column">
                        @lang('modules.deal.addStages')
                    </x-forms.button-secondary>
                @endif
            </div>

            <div class="btn-group mt-2 mt-lg-0 mt-md-0 ml-0 ml-lg-3 ml-md-3" role="group">
                <a href="{{ route('deals.index') }}" class="btn btn-secondary f-14" data-toggle="tooltip"
                    data-original-title="@lang('modules.leaves.tableView')"><i class="side-icon bi bi-list-ul"></i></a>

                <a href="{{ route('leadboards.index') }}" class="btn btn-secondary f-14 btn-active" data-toggle="tooltip"
                    data-original-title="@lang('modules.lead.kanbanboard')"><i class="side-icon bi bi-kanban"></i></a>

            </div>
        </div>

        <div class="w-task-board-panel d-flex" id="taskboard-columns">

        </div>
    </div>
    <!-- CONTENT WRAPPER END -->

@endsection

@push('scripts')
    @include('sections.daterange_js')
    <script src="{{ asset('vendor/jquery/dragula.js') }}"></script>

    <script>
        function showTable() {
            var dateRangePicker = $('#datatableRange').data('daterangepicker');
            var startDate = $('#datatableRange').val();

            if (startDate == '') {
                startDate = null;
                endDate = null;
            } else {
                startDate = dateRangePicker.startDate.format('{{ company()->moment_date_format }}');
                endDate = dateRangePicker.endDate.format('{{ company()->moment_date_format }}');
            }

            var searchText = $('#search-text-field').val();
            var pipeline = $('#pipeline').val();
            var min = $('#min').val();
            var max = $('#max').val();
            var type = $('#type').val();
            var followUp = $('#followUp').val();
            var agent = $('#filter_agent_id').val();
            var category_id = $('#filter_category_id').val();
            var source_id = $('#filter_source_id').val();
            var date_filter_on = $('#date_filter_on').val();
            var status_id = $('#filter_status_id').val();

            var url = "{{ route('leadboards.index') }}?startDate=" + encodeURIComponent(startDate) + '&endDate=' +
                encodeURIComponent(endDate) + '&type=' + type + '&followUp=' + followUp + '&agent=' +
                agent + '&category_id=' + category_id + '&source_id=' + source_id +
                '&searchText=' + searchText  + '&min=' + min + '&max=' + max + '&date_filter_on=' + date_filter_on + '&status_id=' + status_id + '&pipeline=' + pipeline;

            $.easyAjax({
                url: url,
                container: '#taskboard-columns',
                type: "GET",
                success: function(response) {
                    $('#taskboard-columns').html(response.view);
                    $("body").tooltip({
                        selector: '[data-toggle="tooltip"]'
                    });
                }
            });
        }

        $('body').on('click', '.load-more-tasks', function() {
            var columnId = $(this).data('column-id');
            var totalTasks = $(this).data('total-tasks');
            var currentTotalTasks = $('#drag-container-' + columnId + ' .task-card').length;

            var dateRangePicker = $('#datatableRange').data('daterangepicker');
            var startDate = $('#datatableRange').val();

            if (startDate == '') {
                startDate = null;
                endDate = null;
            } else {
                startDate = dateRangePicker.startDate.format('{{ company()->moment_date_format }}');
                endDate = dateRangePicker.endDate.format('{{ company()->moment_date_format }}');
            }

            var min = $('#min').val();
            var max = $('#max').val();
            var type = $('#type').val();
            var followUp = $('#followUp').val();
            var agent = $('#filter_agent_id').val();
            var category_id = $('#filter_category_id').val();
            var source_id = $('#filter_source_id').val();
            var searchText = $('#search-text-field').val();
            var date_filter_on = $('#date_filter_on').val();

            var url = "{{ route('leadboards.load_more') }}?startDate=" + encodeURIComponent(startDate) +
                '&endDate=' +
                encodeURIComponent(endDate) + '&type=' + type + '&followUp=' + followUp + '&agent=' +
                agent + '&category_id=' + category_id + '&source_id=' + source_id +
                '&searchText=' + searchText + '&columnId=' + columnId + '&currentTotalTasks=' + currentTotalTasks +
                '&totalTasks=' + totalTasks + '&min=' + min + '&max=' + max + '&date_filter_on=' + date_filter_on + '&pipeline=' + pipeline;

            $.easyAjax({
                url: url,
                container: '#drag-container-' + columnId,
                blockUI: true,
                type: "GET",
                success: function(response) {
                    $('#drag-container-' + columnId).append(response.view);
                    if (response.load_more == 'show') {
                        $('#drag-container-' + columnId).closest('.b-p-body').find('.load-more-tasks');

                    } else {
                        $('#drag-container-' + columnId).closest('.b-p-body').find('.load-more-tasks')
                            .remove();
                    }

                    $("body").tooltip({
                        selector: '[data-toggle="tooltip"]'
                    });
                }
            });

        });

        var elem = document.getElementById("fullscreen");

        function openFullscreen() {
            if (elem.requestFullscreen) {
                elem.requestFullscreen();
                elem.classList.add("full");
            } else if (elem.mozRequestFullScreen) {
                /* Firefox */
                elem.mozRequestFullScreen();
            } else if (elem.webkitRequestFullscreen) {
                /* Chrome, Safari & Opera */
                elem.webkitRequestFullscreen();
            } else if (elem.msRequestFullscreen) {
                /* IE/Edge */
                elem.msRequestFullscreen();
            }
        }

        $('#add-column').click(function() {
            const url = "{{ route('lead-stage-setting.create') }}";
            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

        $('body').on('click', '.edit-column', function() {
            var statusId = $(this).data('column-id');
            var url = "{{ route('lead-stage-setting.edit', ':id ') }}";
            url = url.replace(':id', statusId);

            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

        $('body').on('click', '.delete-column', function() {
            var id = $(this).data('column-id');
            var url = "{{ route('lead-stage-setting.destroy', ':id') }}";
            url = url.replace(':id', id);

            Swal.fire({
                title: "@lang('messages.sweetAlertTitle')",
                text: "@lang('messages.deal.deleteStage')",
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
                    $.easyAjax({
                        url: url,
                        type: 'POST',
                        data: {
                            '_token': '{{ csrf_token() }}',
                            '_method': 'DELETE'
                        },
                        success: function(response) {
                            if (response.status == 'success') {
                                window.location.reload();
                            }
                        }
                    });
                }
            });

        });


        $('body').on('click', '.collapse-column', function() {
            var boardColumnId = $(this).data('column-id');
            var type = $(this).data('type');

            $.easyAjax({
                url: "{{ route('leadboards.collapse_column') }}",
                type: 'POST',
                container: '#taskboard-columns',
                blockUI: true,
                data: {
                    boardColumnId: boardColumnId,
                    type: type,
                    '_token': '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.status == 'success') {
                        showTable();
                    }
                }
            });
        });

        $('body').on('click', '.delete-table-row', function() {
            var id = $(this).data('id');
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
                    var url = "{{ route('deals.destroy', ':id') }}";
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
                                window.location.href = "{{ route('leadboards.index')}}";
                            }
                        }
                    });
                }
            });
        });

        showTable();
    </script>
@endpush
