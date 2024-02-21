<link rel="stylesheet" href="{{ asset('vendor/css/dropzone.min.css') }}">

<x-form id="createMethods" method="POST" class="ajax-form">
    <div class="modal-header">
        <h5 class="modal-title">@lang('app.newDiscussion')</h5>
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    </div>
    <div class="modal-body">
        <div class="portlet-body">

            <input type="hidden" name="project_id" value="{{ $projectId }}">
            <div class="row">
                <div class="col-md-12">
                    <div class="my-3">
                        <x-forms.select fieldId="discussion_category_id" :fieldLabel="__('app.category')"
                                        fieldName="discussion_category" search="true" fieldRequired="true">
                            @foreach ($categories as $item)
                                <option
                                    data-content="<i class='fa fa-circle mr-2' style='color: {{ $item->color }}'></i>{{ $item->name }}"
                                    value="{{ $item->id }}">{{ $item->name }}
                                </option>
                            @endforeach
                        </x-forms.select>
                    </div>
                </div>

                <div class="col-md-12">
                    <x-forms.text :fieldLabel="__('app.title')" fieldName="title" fieldRequired="true"
                                  fieldId="title" autocomplete="off"/>
                </div>
                <div class="col-md-12">
                    <div class="form-group my-3">
                        <x-forms.label :fieldLabel="__('app.reply')" fieldRequired="true" fieldId="description">
                        </x-forms.label>
                        <div id="description"></div>
                        <textarea name="description" id="description-text" class="d-none"></textarea>
                    </div>
                </div>
                <div class="col-md-12">
                    <x-forms.file-multiple class="mr-0 mr-lg-2 mr-md-2"
                                           :fieldLabel="__('app.menu.addFile')" fieldName="file"
                                           fieldId="discussion-file-upload-dropzone"/>
                    <input type="hidden" name="discussion_id" id="discussion_id">
                    <input type="hidden" name="type" id="discussion">
                </div>
            </div>

        </div>
    </div>
    <div class="modal-footer">
        <x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">@lang('app.cancel')</x-forms.button-cancel>
        <x-forms.button-primary id="save-discussion" icon="check">@lang('app.save')</x-forms.button-primary>
    </div>
</x-form>

<script>
     var atValues = @json($userData);

    quillMention(atValues, '#description');

    var discussion_reply_id;
    /* Upload images */
    Dropzone.autoDiscover = false;
    //Dropzone class
    taskDropzone = new Dropzone("#discussion-file-upload-dropzone", {
        dictDefaultMessage: "{{ __('app.dragDrop') }}",
        url: "{{ route('discussion-files.store') }}",
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        paramName: "file",
        maxFilesize: DROPZONE_MAX_FILESIZE,
        maxFiles: DROPZONE_MAX_FILES,
        autoProcessQueue: false,
        uploadMultiple: true,
        addRemoveLinks: true,
        parallelUploads: DROPZONE_MAX_FILES,
        acceptedFiles: DROPZONE_FILE_ALLOW,
        init: function () {
            taskDropzone = this;
        }
    });
    taskDropzone.on('sending', function (file, xhr, formData) {
        var ids = $('#discussion_id').val();
        formData.append('discussion_id', ids);
        formData.append('type', 'discussion');
        formData.append('discussion_reply_id', discussion_reply_id);
        $.easyBlockUI();
    });
    taskDropzone.on('uploadprogress', function () {
        $.easyBlockUI();
    });
    taskDropzone.on('queuecomplete', function () {
        window.location.href = "{{ route('projects.show', $projectId) }}?tab=discussion";
    });
     taskDropzone.on('removedfile', function () {
         var grp = $('div#file-upload-dropzone').closest(".form-group");
         var label = $('div#file-upload-box').siblings("label");
         $(grp).removeClass("has-error");
         $(label).removeClass("is-invalid");
     });
     taskDropzone.on('error', function (file, message) {
         taskDropzone.removeFile(file);
         var grp = $('div#file-upload-dropzone').closest(".form-group");
         var label = $('div#file-upload-box').siblings("label");
         $(grp).find(".help-block").remove();
         var helpBlockContainer = $(grp);

         if (helpBlockContainer.length == 0) {
             helpBlockContainer = $(grp);
         }

         helpBlockContainer.append('<div class="help-block invalid-feedback">' + message + '</div>');
         $(grp).addClass("has-error");
         $(label).addClass("is-invalid");

     });
    // Save discussion
    $('#save-discussion').click(function () {
        var note = document.getElementById('description').children[0].innerHTML;
        document.getElementById('description-text').value = note;
        var mentionUser = $('#description span[data-id]').map(function(){
                            return $(this).attr('data-id')
                        }).get();

       var mention_user_id  =  $.makeArray(mentionUser);

       var discussionData = $('#createMethods').serialize();

        var data = discussionData+='&mention_user_id=' + mention_user_id;
        $.easyAjax({
            url: "{{ route('discussion.store') }}",
            container: '#createMethods',
            type: "POST",
            blockUI: true,
            disableButton: true,
            buttonSelector: "#save-discussion",
            data: data,
            success: function (response) {
                if (response.status === 'success') {
                    if (taskDropzone.getQueuedFiles().length > 0) {
                        discussion_id = response.discussion_id;
                        $('#discussion_id').val(response.discussion_id);
                        discussion_reply_id = response.discussion_reply_id;
                        taskDropzone.processQueue();
                    } else {
                        window.location.href = "{{ route('projects.show', $projectId) }}?tab=discussion";
                    }
                }
            }
        })
    });

    init('#createMethods');

</script>
