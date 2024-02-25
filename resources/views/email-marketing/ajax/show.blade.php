@php
$editPermission = user()->permission('edit_product');
$deletePermission = user()->permission('delete_product');
@endphp
<div id="product-detail-section">
    <div class="row">
        <div class="col-sm-12">
            <div class="card bg-white border-0 b-shadow-4">
                <div class="card-header bg-white  border-bottom-grey text-capitalize justify-content-between p-20">
                    <div class="row">
                        <div class="col-lg-10 col-10">
                            <h3 class="heading-h1 mb-3">@lang('modules.emailMarketing.emailDetail')</h3>
                        </div>
                        <div class="col-lg-2 col-2 text-right">
                            <!-- @if (
                                ($editPermission == 'all' || ($editPermission == 'added' && $product->added_by == user()->id))
                                || ($deletePermission == 'all' || ($deletePermission == 'added' && $product->added_by == user()->id))
                                ) -->
                                <div class="dropdown">
                                    <button
                                        class="btn btn-lg f-14 px-2 py-1 text-dark-grey text-capitalize rounded  dropdown-toggle"
                                        type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fa fa-ellipsis-h"></i>
                                    </button>

                                    <div class="dropdown-menu dropdown-menu-right border-grey rounded b-shadow-4 p-0"
                                        aria-labelledby="dropdownMenuLink" tabindex="0">
                                        <a class="dropdown-item openRightModal"
                                            href="{{ route('email-marketing.compose', $emailTemplate->id) }}">@lang('modules.emailMarketing.sendEmail')
                                        </a>
                                        <!-- @if ($editPermission == 'all' || ($editPermission == 'added' && $product->added_by == user()->id)) -->
                                            <a class="dropdown-item openRightModal"
                                                href="{{ route('email-marketing.edit', $emailTemplate->id) }}">@lang('app.edit')
                                            </a>
                                        <!-- @endif -->

                                        <!-- @if ($deletePermission == 'all' || ($deletePermission == 'added' && $product->added_by == user()->id)) -->
                                            <a class="dropdown-item delete-product"
                                                data-email-id="{{ $emailTemplate->id }}">@lang('app.delete')</a>
                                        <!-- @endif -->
                                    </div>
                                </div>
                            <!-- @endif -->
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <x-cards.data-row :label="__('app.title')" :value="$emailTemplate->title ?? '--'" />
                            <x-cards.data-row :label="__('modules.emailMarketing.content')" :value="$emailTemplate->content ?? '--'" :html="1"/>
                            <x-cards.data-row :label="__('app.addedBy')" :value="$emailTemplate->addedBy ?? '--'" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $('body').on('click', '.delete-product', function() {
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
                let url = "{{ route('products.destroy', ':id') }}";
                url = url.replace(':id', id);

                const token = "{{ csrf_token() }}";

                $.easyAjax({
                    type: 'POST',
                    url: url,
                    data: {
                        '_token': token,
                        '_method': 'DELETE'
                    },
                    success: function(response) {
                        if (response.status === "success") {
                            window.location.href = response.redirectUrl;
                        }
                    }
                });
            }
        });
    });

</script>
