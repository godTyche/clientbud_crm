<!-- ROW START -->
<div class="row">
    <!--  USER CARDS START -->
    <div class="col-xl-12 col-lg-12 col-md-12 mb-4 mb-xl-0 mb-lg-4 mb-md-0">

        <x-cards.data :title="__('modules.deal.dealInfo')">

            <x-slot name="action">
                <div class="dropdown">
                    <button class="btn f-14 px-0 py-0 text-dark-grey dropdown-toggle" type="button"
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-ellipsis-h"></i>
                    </button>

                    <div class="dropdown-menu dropdown-menu-right border-grey rounded b-shadow-4 p-0"
                        aria-labelledby="dropdownMenuLink" tabindex="0">
                        <a class="dropdown-item openRightModal"
                            href="{{ route('deals.edit', $deal->id) }}">@lang('app.edit')</a>
                        @if (
                            $deleteLeadPermission == 'all'
                            || ($deleteLeadPermission == 'added' && user()->id == $deal->added_by)
                            || ($deleteLeadPermission == 'owned' && !is_null($deal->agent_id) && user()->id == $deal->leadAgent->user->id)
                            || ($deleteLeadPermission == 'both' && ((!is_null($deal->agent_id) && user()->id == $deal->leadAgent->user->id)
                                    || user()->id == $deal->added_by))
                        )
                            <a class="dropdown-item delete-table-row" href="javascript:;" data-id="{{ $deal->id }}">
                                    @lang('app.delete')
                                </a>
                        @endif

                    </div>
                </div>
            </x-slot>

            <x-cards.data-row :label="__('modules.deal.dealName')" :value="$deal->name ?? '--'" />

            <x-cards.data-row :label="__('modules.leadContact.leadContact')" :value="$deal->contact->client_name ?? '--'" />

            <x-cards.data-row :label="__('app.email')" :value="$deal->contact->client_email ?? '--'" />

            <x-cards.data-row :label="__('modules.lead.companyName')" :value="!empty($deal->contact->company_name) ? $deal->contact->company_name : '--'" />

            <div class="col-12 px-0 pb-3 d-flex">
                <p class="mb-0 text-lightest f-14 w-30 d-inline-block text-capitalize">
                    @lang('modules.deal.dealAgent')</p>
                <p class="mb-0 text-dark-grey f-14">
                    @if (!is_null($deal->leadAgent))
                        <x-employee :user="$deal->leadAgent->user" />
                    @else
                        --
                    @endif
                </p>
            </div>

            @if ($deal->leadStatus)
                <div class="col-12 px-0 pb-3 d-flex">
                    <p class="mb-0 text-lightest f-14 w-30 d-inline-block text-capitalize">@lang('app.status')</p>
                    <p class="mb-0 text-dark-grey f-14">
                        <x-status :value="$deal->leadStatus->type"
                            :style="'color:'.$deal->leadStatus->label_color" />
                    </p>

                </div>
            @endif

            <x-cards.data-row :label="__('modules.deal.closeDate')" :value="($deal->close_date) ? $deal->close_date->translatedFormat(company()->date_format) : '--'" />
            <x-cards.data-row :label="__('modules.deal.dealValue')" :value="($deal->value) ? currency_format($deal->value, $deal->currency_id) : '--'" />

            <x-cards.data-row :label="__('modules.lead.products')" :value="($productNames) ? implode(', ' , $productNames) : '--'" />

            {{-- Custom fields data --}}
            <x-forms.custom-field-show :fields="$fields" :model="$deal"></x-forms.custom-field-show>

        </x-cards.data>
    </div>
    <!--  USER CARDS END -->
</div>
<!-- ROW END -->
