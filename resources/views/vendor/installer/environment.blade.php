@extends('vendor.installer.layouts.master')

@section('title', trans('installer_messages.environment.title'))
@section('style')
    <link href="{{ asset('installer/froiden-helper/helper.css') }}" rel="stylesheet"/>
    <style>
        .has-error {
            color: red;
        }

        .help-block {
            font-size: 12px;
        }

        .has-error input {
            color: black;
            border: 1px solid red;
        }

    </style>
@endsection
@section('container')
    <form method="post" action="{{ route('LaravelInstaller::environmentSave') }}" id="env-form">
        <div class="row">
            <div class="col-sm-12">
                <div class="form-group">
                    <label class="control-label">Hostname</label>
                    <input type="text" name="hostname" class="form-control" value="localhost">
                </div>

                <div class="form-group">

                    <label class="control-label">Database username</label>
                    <input type="text" name="username" class="form-control">
                </div>
                <div class="form-group">
                    <label class="control-label">Database password</label>
                    <div class="col-sm-12">
                        <input type="password" class="form-control" name="password">
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label">Database name</label>
                    <div class="col-sm-12">
                        <input type="text" name="database" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="buttons">
                        <button class="button" onclick="checkEnv();return false">
                            {{ trans('installer_messages.next') }}
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </form>
    <script>
        function checkEnv() {
            $.easyAjax({
                url: "{!! route('LaravelInstaller::environmentSave') !!}",
                type: "GET",
                data: $("#env-form").serialize(),
                container: "#env-form",
                disableButton: true,
                blockUI: true,
                buttonSelector: ".button",
                messagePosition: "inline"
            });
        }
    </script>
@stop
@section('scripts')
    <script src="{{ asset('installer/js/jQuery-2.2.0.min.js') }}"></script>
    <script src="{{ asset('installer/froiden-helper/helper.js') }}"></script>
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

    </script>
@endsection
