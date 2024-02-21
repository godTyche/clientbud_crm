@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/css/dropzone.min.css') }}">
    <style>
        .dropzone .dz-preview .dz-error-message {
            top: 150px !important;
        }

    </style>
@endpush

@section('content')

    <!-- SETTINGS START -->
    <div class="w-100 d-flex ">

        @include('sections.setting-sidebar')

        <x-setting-card>
            <x-slot name="header">
                <div class="s-b-n-header" id="tabs">
                    <h2 class="mb-0 p-20 f-21 font-weight-normal text-capitalize border-bottom-grey">
                        @lang($pageTitle)</h2>
                </div>
            </x-slot>

            <div class="col-lg-12 col-md-12 ntfcn-tab-content-left w-100 p-4 ">
                <h4 class="f-21 font-weight-normal text-capitalize ">
                    @lang('modules.moduleSettings.step1')</h4>


                <div class="row">
                    <div class="col-sm-12">

                        @php
                            $uploadMaxFilesize = \App\Helper\Files::getUploadMaxFilesize();
                            $postMaxSize = \App\Helper\Files::getPostMaxSize();
                        @endphp

                        @if(!$uploadMaxFilesize)
                            <span class="text-danger">
                                    Your Server upload_max_filesize = {{\App\Helper\Files::getUploadMaxFilesize()['size']}}.
                                    Please change to min <strong>{{\App\Helper\Files::REQUIRED_FILE_UPLOAD_SIZE}}MB</strong>
                                    to upload big modules
                            </span>
                        @elseif(!$postMaxSize)
                            <span class="text-danger">
                                    Your Server post_max_size = {{\App\Helper\Files::getUploadMaxFilesize()['size']}}.
                                    Please change to min <strong>{{\App\Helper\Files::REQUIRED_FILE_UPLOAD_SIZE}}MB</strong> to
                                    upload big modules
                            </span>
                        @endif

                        <x-forms.file-multiple
                            class="mr-0 mr-lg-2 mr-md-2"
                            :fieldLabel=" __('messages.downloadFilefromCodecanyon') " fieldName="file"
                            fieldId="file-upload-dropzone"/>
                    </div>
                </div>
            </div>

            <div class="col-md-12 " id="install-process"></div>

            <div class="col-lg-12 col-md-12 ntfcn-tab-content-left w-100 p-4 ">
                <h4 class="f-21 font-weight-normal text-capitalize">
                    @lang('modules.moduleSettings.step2')</h4>

                <p>@lang('modules.update.moduleFile')</p>
            </div>
            <div class="col-md-12 mb-3">
                <ul class="list-group" id="files-list">
                    @foreach (\Illuminate\Support\Facades\File::files($updateFilePath) as $key => $filename)
                        @if (\Illuminate\Support\Facades\File::basename($filename) != 'modules_statuses.json' && strpos(\Illuminate\Support\Facades\File::basename($filename), 'auto') === false)
                            <li class="list-group-item" id="file-{{ $key + 1 }}">
                                <div class="row">
                                    <div class="col-lg-6 py-1">
                                        <b>{{ \Illuminate\Support\Facades\File::basename($filename) }}</b>
                                    </div>

                                    <div class="col-lg-4 py-1 text-center f-12">
                                        @lang('app.uploadDate'):
                                        {{ \Carbon\Carbon::parse(\Illuminate\Support\Facades\File::lastModified($filename))->timezone(global_setting()->timezone)->translatedFormat('jS M, Y g:i A') }}
                                    </div>

                                    <div class="col-lg-2 text-lg-right py-1">
                                        <button type="button"
                                                class="btn btn-primary p-1 f-13 btn-sm mr-2 install-files"
                                                data-file-no="{{ $key + 1 }}"
                                                data-file-path="{{ $filename }}">@lang('modules.update.install') <i
                                                class="fa fa-download"></i>
                                        </button>

                                        <button type="button"
                                                class="btn btn-light f-13 btn-sm delete-files"
                                                data-file-no="{{ $key + 1 }}" data-toggle="tooltip"
                                                data-original-title="@lang('app.delete')"
                                                data-file-path="{{ $filename }}">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>

            <x-slot name="action">
                <!-- Buttons Start -->
                <div class="w-100 border-top-grey">
                    <x-setting-form-actions>
                        <a href="{{ route('custom-modules.index').'?tab=custom' }}" class="btn-secondary rounded f-14 p-2">
                            @lang('app.back')
                        </a>
                    </x-setting-form-actions>
                    <div class="d-block d-lg-none d-md-none p-4">
                        <x-forms.button-cancel :link="route('custom-modules.index').'?tab=custom'" class="w-100 mt-3">
                            @lang('app.cancel')
                        </x-forms.button-cancel>
                    </div>
                </div>
                <!-- Buttons End -->
            </x-slot>

        </x-setting-card>

    </div>
    <!-- SETTINGS END -->

@endsection

@push('scripts')
    <script>
        Dropzone.autoDiscover = false;
        $(document).ready(function () {
            const uploadFile = "{{ route('update-settings.store') }}?_token={{ csrf_token() }}";
            const myDrop = new Dropzone("#file-upload-dropzone", {
                url: uploadFile,
                acceptedFiles: 'application/zip, application/x-zip-compressed, application/x-compressed, multipart/x-zip',
                addRemoveLinks: true,
                dictDefaultMessage: "@lang('app.dropFileToUpload')",
            });
            myDrop.on("complete", function (file) {
                if (myDrop.getRejectedFiles().length == 0) {
                    window.location.reload();
                }
            });
        });

        $('.install-files').click(function () {

            $('#install-process').html('<div class="alert alert-primary">@lang("messages.installingUpdateMessage")</div>');

            let filePath = $(this).data('file-path');
            $.easyAjax({
                type: 'POST',
                url: "{{ route('custom-modules.store') }}",
                blockUI: true,
                data: {
                    "_token": "{{ csrf_token() }}",
                    filePath: filePath
                },
                success: function (response) {
                    $('#install-process').html('');

                    if (response.status === 'success') {
                        $.easyBlockUI('body')
                        $('#install-process').html(`<div class="alert alert-success">@lang('messages.customModuleInstalled')</div>`);
                        window.location.href = "{{ route('custom-modules.index').'?tab=custom' }}";
                    }

                    if (response.status === 'fail') {
                        $('#install-process').html(`<div class="alert alert-danger">${response.message}</div>`);
                    }
                }
            });
        });

        $('.delete-files').click(function () {
            let filePath = $(this).data('file-path');
            let fileNumber = $(this).data('file-no');

            Swal.fire({
                title: "@lang('messages.sweetAlertTitle')",
                text: "@lang('messages.removeFileText')",
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
                        type: 'POST',
                        url: "{{ route('update-settings.deleteFile') }}",
                        blockUI: true,
                        data: {
                            "_token": "{{ csrf_token() }}",
                            filePath: filePath
                        },
                        success: function (response) {
                            $('#file-' + fileNumber).remove();
                        }
                    });
                }
            });


        });

    </script>
@endpush
