<style>
    #logo {
        height: 50px;
    }

</style>

<!-- INVOICE CARD START -->
@if (!is_null($invoice->project) && !is_null($invoice->project->client) && !is_null($invoice->project->client->clientDetails))
    @php
        $client = $invoice->project->client;
    @endphp
@elseif(!is_null($invoice->client_id) && !is_null($invoice->clientDetails))
    @php
        $client = $invoice->client;
    @endphp
@endif

<div class="d-lg-flex">
    <div class="w-100 py-0 py-md-0 ">
        <x-cards.data :title="__('app.recurring') . ' ' . __('app.details')" class=" mt-4">
            <div class="col-12 px-0 pb-3 d-block d-lg-flex d-md-flex">
                <p class="mb-0 text-lightest f-14 w-30 d-inline-block text-capitalize">
                    @lang('modules.recurringInvoice.completedTotalInvoice')</p>
                <p class="mb-0 text-dark-grey f-14 ">
                    @if(!is_null($invoice->billing_cycle))
                        {{$invoice->recurrings->count()}}/{{$invoice->billing_cycle}}
                    @else
                        {{$invoice->recurrings->count()}}/<span class="px-1"><label class="badge badge-primary">@lang('app.infinite')</label></span>
                    @endif
                </p>
            </div>
            @if (count($invoice->recurrings) > 0)
                <x-cards.data-row :label="__('modules.recurringInvoice.lastInvoiceDate')"
                :value="$invoice->recurrings->last()->issue_date->translatedFormat(company()->date_format)" />
            @else
                <x-cards.data-row :label="__('modules.recurringInvoice.firstInvoiceDate')"
                :value="$invoice->issue_date ? $invoice->issue_date->translatedFormat(company()->date_format) : '----'" />
            @endif

            <x-cards.data-row :label="__('modules.recurringInvoice.nextInvoice').' '.__('app.date')"
            :value="$invoice->next_invoice_date ? $invoice->next_invoice_date->translatedFormat(company()->date_format) : '----'" />
        </x-cards.data>
    </div>
</div>
<div class="d-lg-flex">

    <div class="w-100 py-0 py-lg-4 py-md-0 ">

        <div class="card border-0 invoice">
            <!-- CARD BODY START -->
            <div class="card-body">

                <div class="invoice-table-wrapper">
                    <table width="100%" class="">
                        <tr class="inv-logo-heading">
                            <td><img src="{{ invoice_setting()->logo_url }}"
                                    alt="{{ company()->company_name }}" id="logo" /></td>
                            <td align="right"
                                class="font-weight-bold f-21 text-dark text-uppercase mt-4 mt-lg-0 mt-md-0">
                                @lang('app.invoice')</td>
                        </tr>
                        <tr class="inv-num">
                            <td class="f-14 text-dark">
                                <p class="mt-3 mb-0">
                                    {{ company()->company_name }}<br>
                                    @if (!is_null($settings))
                                        {!! nl2br(default_address()->address) !!}<br>
                                        {{ company()->company_phone }}
                                    @endif
                                    @if ($invoiceSetting->show_gst == 'yes' && $invoice->address)
                                        <br>{{ $invoice->address->tax_name }}: {{ $invoice->address->tax_number }}
                                    @endif
                                </p><br>
                            </td>
                            <td align="right">
                                <table class="inv-num-date text-dark f-13 mt-3">
                                    <tr>
                                        <td class="bg-light-grey border-right-0 f-w-500">
                                            @lang('modules.invoices.invoiceDate')</td>
                                        <td class="border-left-0">
                                            {{ $invoice->issue_date->translatedFormat(company()->date_format) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="bg-light-grey border-right-0 f-w-500">@lang('app.dueDate')</td>
                                        <td class="border-left-0">
                                            {{ $invoice->due_date->translatedFormat(company()->date_format) }}
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td height="20"></td>
                        </tr>
                    </table>
                    <table width="100%">
                        <tr class="inv-unpaid">
                            <td class="f-14 text-dark">
                                <p class="mb-0 text-left"><span
                                        class="text-dark-grey text-capitalize">@lang('modules.invoices.billedTo')</span><br>

                                    @php
                                        if ($invoice->project && $invoice->project->client) {
                                            $client = $invoice->project->client;
                                        } elseif ($invoice->client_id != '') {
                                            $client = $invoice->client;
                                        } elseif ($invoice->estimate && $invoice->estimate->client) {
                                            $client = $invoice->estimate->client;
                                        }
                                    @endphp

                                    {{ $client->name }}<br>
                                    {{ $client->clientDetails->company_name }}<br>
                                    {!! nl2br($client->clientDetails->address) !!}</p>
                            </td>
                            @if ($invoice->shipping_address)
                                <td class="f-14 text-black">
                                    <p class="mb-0 text-left"><span
                                            class="text-dark-grey text-capitalize">@lang('app.shippingAddress')</span><br>
                                        {!! nl2br($client->clientDetails->address) !!}</p>
                                </td>
                            @endif
                        </tr>
                        <tr>
                            <td height="30" colspan="2"></td>
                        </tr>
                    </table>
                    <table width="100%" class="inv-desc d-none d-lg-table d-md-table">
                        <tr>
                            <td colspan="2">
                                <table class="inv-detail f-14 table-responsive-sm" width="100%">
                                    <tr class="i-d-heading bg-light-grey text-dark-grey font-weight-bold">
                                        <td class="border-right-0">@lang('app.description')</td>
                                        @if ($invoiceSetting->hsn_sac_code_show)
                                            <td class="border-right-0 border-left-0" align="right">@lang('app.hsnSac')
                                        @endif
                                        <td class="border-right-0 border-left-0" align="right">@lang('modules.invoices.qty')
                                        </td>
                                        <td class="border-right-0 border-left-0" align="right">
                                            @lang('modules.invoices.unitPrice') ({{ $invoice->currency->currency_code }})
                                        </td>
                                        <td class="border-left-0" align="right">
                                            @lang('modules.invoices.amount')
                                            ({{ $invoice->currency->currency_code }})</td>
                                    </tr>
                                    @foreach ($invoice->items as $item)
                                        @if ($item->type == 'item')
                                            <tr class="text-dark">
                                                <td>{{ $item->item_name }}</td>
                                                @if ($invoiceSetting->hsn_sac_code_show)
                                                    <td align="right">{{ $item->hsn_sac_code }}</td>
                                                @endif
                                                <td align="right">{{ $item->quantity }}@if($item->unit)<br><span class="f-11 text-dark-grey">{{ $item->unit->unit_type }}</span>@endif</td>
                                                <td align="right">
                                                    {{ number_format((float) $item->unit_price, 2, '.', '') }}</td>
                                                <td align="right">
                                                    {{ number_format((float) $item->amount, 2, '.', '') }}
                                                </td>
                                            </tr>
                                            @if ($item->item_summary != '' || $item->recurringInvoiceItemImage)
                                                <tr class="text-dark">
                                                    <td colspan="{{ $invoiceSetting->hsn_sac_code_show ? '5' : '4' }}"
                                                        class="border-bottom-0">{!! nl2br(strip_tags($item->item_summary)) !!}
                                                        @if ($item->recurringInvoiceItemImage)
                                                            <p class="mt-2">
                                                                <a href="javascript:;" class="img-lightbox"
                                                                    data-image-url="{{ $item->recurringInvoiceItemImage->file_url }}">
                                                                    <img src="{{ $item->recurringInvoiceItemImage->file_url }}"
                                                                        width="80" height="80" class="img-thumbnail">
                                                                </a>
                                                            </p>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endif
                                        @endif
                                    @endforeach
                                    <p class="mb-0">
                                        @if ($invoiceSetting->show_project == 1 && isset($invoice->project->project_name))
                                            <span class="text-dark-grey text-capitalize">@lang('modules.invoices.projectName')</span><br>
                                            {{ $invoice->project->project_name }}
                                        @endif
                                        <br><br>
                                    </p>
                                    <tr>
                                        <td colspan="2" class="blank-td border-bottom-0 border-left-0 border-right-0">
                                        </td>
                                        <td colspan="3" class="p-0 ">
                                            <table width="100%">
                                                <tr class="text-dark-grey" align="right">
                                                    <td class="w-50 border-top-0 border-left-0">
                                                        @lang('modules.invoices.subTotal')</td>
                                                    <td class="border-top-0 border-right-0">
                                                        {{ number_format((float) $invoice->sub_total, 2, '.', '') }}
                                                    </td>
                                                </tr>
                                                @if ($discount != 0 && $discount != '')
                                                    <tr class="text-dark-grey" align="right">
                                                        <td class="w-50 border-top-0 border-left-0">
                                                            @lang('modules.invoices.discount')</td>
                                                        <td class="border-top-0 border-right-0">
                                                            {{ number_format((float) $discount, 2, '.', '') }}</td>
                                                    </tr>
                                                @endif
                                                @foreach ($taxes as $key => $tax)
                                                    <tr class="text-dark-grey" align="right">
                                                        <td class="w-50 border-top-0 border-left-0">
                                                            {{ $key }}</td>
                                                        <td class="border-top-0 border-right-0">
                                                            {{ number_format((float) $tax, 2, '.', '') }}</td>
                                                    </tr>
                                                @endforeach
                                                <tr class=" text-dark-grey font-weight-bold" align="right">
                                                    <td class="w-50 border-bottom-0 border-left-0">
                                                        @lang('modules.invoices.total')</td>
                                                    <td class="border-bottom-0 border-right-0">
                                                        {{ number_format((float) $invoice->total, 2, '.', '') }}</td>
                                                </tr>

                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>

                        </tr>
                    </table>
                    <table width="100%" class="inv-desc-mob d-block d-lg-none d-md-none">

                        @foreach ($invoice->items as $item)
                            @if ($item->type == 'item')
                                <tr>
                                    <th width="50%" class="bg-light-grey text-dark-grey font-weight-bold">
                                        @lang('app.description')</th>
                                    <td class="p-0 ">
                                        <table>
                                            <tr width="100%">
                                                <td class="border-left-0 border-right-0 border-top-0">
                                                    {{ $item->item_name }}</td>
                                            </tr>
                                            @if ($item->item_summary != '')
                                                <tr>
                                                    <td class="border-left-0 border-right-0 border-bottom-0">
                                                        {!! nl2br(strip_tags($item->item_summary)) !!}</td>
                                                </tr>
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <th width="50%" class="bg-light-grey text-dark-grey font-weight-bold">
                                        @lang('modules.invoices.qty')
                                    </th>
                                    <td width="50%">{{ $item->quantity }}@if($item->unit)<br><span class="f-11 text-dark-grey">{{ $item->unit->unit_type }}</span>@endif</td>
                                </tr>
                                <tr>
                                    <th width="50%" class="bg-light-grey text-dark-grey font-weight-bold">
                                        @lang('modules.invoices.unitPrice')
                                        ({{ $invoice->currency->currency_code }})</th>
                                    <td width="50%">{{ number_format((float) $item->unit_price, 2, '.', '') }}</td>
                                </tr>
                                <tr>
                                    <th width="50%" class="bg-light-grey text-dark-grey font-weight-bold">
                                        @lang('modules.invoices.amount')
                                        ({{ $invoice->currency->currency_code }})</th>
                                    <td width="50%">{{ number_format((float) $item->amount, 2, '.', '') }}</td>
                                </tr>
                                <tr>
                                    <td height="3" class="p-0 " colspan="2"></td>
                                </tr>
                            @endif
                        @endforeach

                        <tr>
                            <th width="50%" class="text-dark-grey font-weight-normal">@lang('modules.invoices.subTotal')
                            </th>
                            <td width="50%" class="text-dark-grey font-weight-normal">
                                {{ number_format((float) $invoice->sub_total, 2, '.', '') }}</td>
                        </tr>
                        @if ($discount != 0 && $discount != '')
                            <tr>
                                <th width="50%" class="text-dark-grey font-weight-normal">@lang('modules.invoices.discount')
                                </th>
                                <td width="50%" class="text-dark-grey font-weight-normal">
                                    {{ number_format((float) $discount, 2, '.', '') }}</td>
                            </tr>
                        @endif

                        @foreach ($taxes as $key => $tax)
                            <tr>
                                <th width="50%" class="text-dark-grey font-weight-normal">{{ $key }}</th>
                                <td width="50%" class="text-dark-grey font-weight-normal">
                                    {{ number_format((float) $tax, 2, '.', '') }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <th width="50%" class="text-dark-grey font-weight-bold">@lang('modules.invoices.total')</th>
                            <td width="50%" class="text-dark-grey font-weight-bold">
                                {{ number_format((float) $invoice->total, 2, '.', '') }}</td>
                        </tr>
                    </table>
                    <table class="inv-note">
                        <tr>
                            <td height="30" colspan="2"></td>
                        </tr>
                        <tr>
                            <td>
                                <table>
                                    <tr>@lang('app.note')</tr>
                                    <tr>
                                        <p class="text-dark-grey">{!! $invoice->note ?? '--' !!}</p>
                                    </tr>
                                </table>
                            </td>
                            <td align="right">
                                <table>
                                    <tr>@lang('modules.invoiceSettings.invoiceTerms')</tr>
                                    <tr>
                                        <p class="text-dark-grey">{!! nl2br($invoiceSetting->invoice_terms) !!}</p>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <!-- CARD BODY END -->

        </div>

    </div>
</div>
<!-- INVOICE CARD END -->
