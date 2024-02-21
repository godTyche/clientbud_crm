<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Template CSS -->
    <title>@lang('modules.lead.proposalTemplate') - {{ $proposalTemplate->id }}</title>
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="{{ global_setting()->favicon_url }}">
    <meta name="theme-color" content="#ffffff">

    @if (invoice_setting()->locale == 'ru')
        <style>
            body {
                margin: 0;
                font-family: dejavu sans;
                font-size: 13px;
            }
        </style>
    @else
        <style>
            body {
                margin: 0;
                font-family: Verdana, Arial, Helvetica, sans-serif;
                font-size: 13px;
            }
        </style>
    @endif

    <style>

        .bg-grey {
            background-color: #F2F4F7;
        }

        .bg-white {
            background-color: #fff;
        }

        .border-radius-25 {
            border-radius: 0.25rem;
        }

        .p-25 {
            padding: 1.25rem;
        }

        .f-11 {
            font-size: 11px;
        }

        .f-12 {
            font-size: 12px;
        }

        .f-13 {
            font-size: 13px;
        }

        .f-14 {
            font-size: 13px;
        }

        .f-15 {
            font-size: 13px;
        }

        .f-21 {
            font-size: 17px;
        }

        .text-black {
            color: #28313c;
        }

        .text-grey {
            color: #616e80;
        }

        .font-weight-700 {
            font-weight: 700;
        }

        .text-uppercase {
            text-transform: uppercase;
        }

        .text-capitalize {
            text-transform: capitalize;
        }

        .line-height {
            line-height: 20px;
        }

        .description {
            line-height: 12px;
        }
        .mt-1 {
            margin-top: 1rem;
        }

        .mb-0 {
            margin-bottom: 0px;
        }

        .b-collapse {
            border-collapse: collapse;
        }

        .heading-table-left {
            padding: 6px;
            border: 1px solid #DBDBDB;
            font-weight: bold;
            background-color: #f1f1f3;
            border-right: 0;
        }

        .heading-table-right {
            padding: 6px;
            border: 1px solid #DBDBDB;
            border-left: 0;
        }

        .unpaid {
            color: #000000;
            border: 1px solid #000000;
            position: relative;
            padding: 11px 22px;
            font-size: 14px;
            border-radius: 0.25rem;
            width: 100px;
            text-align: center;
        }

        .main-table-heading {
            border: 1px solid #DBDBDB;
            background-color: #f1f1f3;
            font-weight: 700;
        }

        .main-table-heading td {
            padding: 5px 8px;
            border: 1px solid #DBDBDB;
        }

        .main-table-items td {
            padding: 5px 8px;
            border: 1px solid #e7e9eb;
        }

        .total-box {
            border: 1px solid #e7e9eb;
            padding: 0px;
            border-bottom: 0px;
        }

        .subtotal {
            padding: 5px 8px;
            border: 1px solid #e7e9eb;
            border-top: 0;
            border-left: 0;
            border-right: 0;
        }

        .subtotal-amt {
            padding: 5px 8px;
            border: 1px solid #e7e9eb;
            border-top: 0;
            border-left: 0;
            border-right: 0;
        }

        .total {
            padding: 5px 8px;
            border: 1px solid #e7e9eb;
            border-top: 0;
            font-weight: 700;
            border-left: 0;
            border-right: 0;
        }

        .total-amt {
            padding: 5px 8px;
            border: 1px solid #e7e9eb;
            border-top: 0;
            border-left: 0;
            border-right: 0;
            font-weight: 700;
        }

        .balance {
            font-size: 14px;
            font-weight: bold;
            background-color: #f1f1f3;
        }

        .balance-left {
            padding: 5px 8px;
            border: 1px solid #e7e9eb;
            border-top: 0;
            border-left: 0;
            border-right: 0;
        }

        .balance-right {
            padding: 5px 8px;
            border: 1px solid #e7e9eb;
            border-top: 0;
            border-right: 0;
            border-left: 0;
        }

        .centered {
            margin: 0 auto;
        }

        .rightaligned {
            margin-right: 0;
            margin-left: auto;
        }

        .leftaligned {
            margin-left: 0;
            margin-right: auto;
        }

        .page_break {
            page-break-before: always;
        }

        #logo {
            height: 50px;
        }

        .word-break {
            max-width: 175px;
            word-wrap: break-word;
            word-break: break-all;
        }

        .summary {
            padding: 11px 10px;
            border: 1px solid #e7e9eb;
            font-size: 11px;
        }

        .border-left-0 {
            border-left: 0 !important;
        }

        .border-right-0 {
            border-right: 0 !important;
        }

        .border-top-0 {
            border-top: 0 !important;
        }

        .border-bottom-0 {
            border-bottom: 0 !important;
        }
    </style>
</head>

<body class="content-wrapper">
    <table class="bg-white" border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation">
        <tbody>
            <!-- Table Row Start -->
            <tr>
                <td><img src="{{ invoice_setting()->logo_url }}" alt="{{ global_setting(->company_name) }}"
                        id="logo" /></td>
                <td align="right" class="f-21 text-black font-weight-700 text-uppercase">@lang('modules.lead.proposalTemplate')
                </td>
            </tr>
            <!-- Table Row End -->
            <!-- Table Row Start -->
            <tr>
                <td>
                    <p class="line-height mt-1 mb-0 f-14 text-black">
                        {{ global_setting()->company_name }}<br>
                        @if (!is_null($settings))
                            {!! nl2br(default_address()->address) !!}<br>
                            {{ global_setting()->company_phone }}
                        @endif
                        @if ($invoiceSetting->show_gst == 'yes' && !is_null($invoiceSetting->gst_number))
                            <br>@lang('app.gstIn'): {{ $invoiceSetting->gst_number }}
                        @endif
                    </p>
                </td>
                <td>
                    <table class="text-black mt-1 f-13 b-collapse rightaligned">
                        <tr>
                            <td class="heading-table-left">@lang('modules.lead.proposalTemplate')</td>
                            <td class="heading-table-right">#{{ $proposalTemplate->id }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
            <!-- Table Row End -->
            <!-- Table Row Start -->
            <tr>
                <td height="10"></td>
            </tr>
            <!-- Table Row End -->
            <!-- Table Row Start -->
            <tr>
                <td height="20"></td>
            </tr>
        </tbody>
    </table>

    @if ($proposalTemplate->description)
        <div class="f-13 description">{!! pdfStripTags($proposalTemplate->description) !!}</div>
    @endif

    <table width="100%" class="f-14 b-collapse">
        <tr>
            <td height="10" colspan="2"></td>
        </tr>
        <!-- Table Row Start -->
        <tr class="main-table-heading text-grey">
            <td width="40%">@lang('app.description')</td>
            @if ($invoiceSetting->hsn_sac_code_show)
                <td align="right" width="10%">@lang("app.hsnSac")</td>
            @endif
            <td align="right" width="10%">@lang('modules.invoices.qty')</td>
            <td align="right">@lang("modules.invoices.unitPrice")</td>
            <td align="right">@lang("modules.invoices.tax")</td>
            <td align="right" width="{{ $invoiceSetting->hsn_sac_code_show ? '17%' : '20%' }}">@lang("modules.invoices.amount")
                ({{ $proposalTemplate->currency->currency_code }})</td>
        </tr>
        <!-- Table Row End -->
        @foreach ($proposalTemplate->items as $item)
            @if ($item->type == 'item')
                <!-- Table Row Start -->
                <tr class="main-table-items text-black f-14">
                    <td width="40%" class="border-bottom-0">{{ $item->item_name }}</td>
                    @if ($invoiceSetting->hsn_sac_code_show)
                        <td align="right" class="border-bottom-0" width="10%">{{ $item->hsn_sac_code ? $item->hsn_sac_code : '--' }}</td>
                    @endif
                    <td align="right" class="border-bottom-0" width="10%">{{ $item->quantity }}<br><span class="f-11 text-grey">{{ $item->unit->unit_type }}</td>
                    <td align="right" class="border-bottom-0">{{ currency_format($item->unit_price, $proposalTemplate->currency_id, false) }}</td>
                    <td align="right" class="border-bottom-0">{{ $item->tax_list }}</td>
                    <td align="right" class="border-bottom-0">{{ currency_format($item->amount, $proposalTemplate->currency_id, false) }}</td>
                </tr>
                @if ($item->item_summary != '' || $item->proposalTemplateItemImage)
                    </table>
                    <div class="f-13 summary text-black border-bottom-0">
                        {!! nl2br(pdfStripTags($item->item_summary)) !!}
                        @if ($item->proposalTemplateItemImage)
                            <p class="mt-2">
                                <img src="{{ $item->proposalTemplateItemImage->file_url }}" width="60" height="60"
                                    class="img-thumbnail">
                            </p>
                        @endif
                    </div>
                    <table class="bg-white" border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation">
                @endif

            @endif
        @endforeach
        <!-- Table Row Start -->
        <tr>
            <td class="total-box" align="right" colspan="{{ $invoiceSetting->hsn_sac_code_show ? '5' : '4' }}">
                <table width="100%" border="0" class="b-collapse">
                    <!-- Table Row Start -->
                    <tr align="right" class="text-grey">
                        <td width="50%" class="subtotal">@lang("modules.invoices.subTotal")</td>
                    </tr>
                    <!-- Table Row End -->
                    @if ($discount != 0 && $discount != '')
                        <!-- Table Row Start -->
                        <tr align="right" class="text-grey">
                            <td width="50%" class="subtotal">@lang("modules.invoices.discount")
                            </td>
                        </tr>
                        <!-- Table Row End -->
                    @endif
                    @foreach ($taxes as $key => $tax)
                        <!-- Table Row Start -->
                        <tr align="right" class="text-grey">
                            <td width="50%" class="subtotal">{{ $key }}</td>
                        </tr>
                        <!-- Table Row End -->
                    @endforeach
                    <!-- Table Row Start -->
                    <tr align="right" class="balance text-black">
                        <td width="50%" class="balance-left">@lang("modules.invoices.total")</td>
                    </tr>
                    <!-- Table Row End -->

                </table>
            </td>
            <td class="total-box" align="right" width="{{ $invoiceSetting->hsn_sac_code_show ? '17%' : '20%' }}">
                <table width="100%" class="b-collapse">
                    <!-- Table Row Start -->
                    <tr align="right" class="text-grey">
                        <td class="subtotal-amt">
                            {{ currency_format($proposalTemplate->sub_total, $proposalTemplate->currency_id, false) }}</td>
                    </tr>
                    <!-- Table Row End -->
                    @if ($discount != 0 && $discount != '')
                        <!-- Table Row Start -->
                        <tr align="right" class="text-grey">
                            <td class="subtotal-amt">
                                {{ currency_format($discount, $proposalTemplate->currency_id, false) }}</td>
                        </tr>
                        <!-- Table Row End -->
                    @endif
                    @foreach ($taxes as $key => $tax)
                        <!-- Table Row Start -->
                        <tr align="right" class="text-grey">
                            <td class="subtotal-amt">{{ currency_format($tax, $proposalTemplate->currency_id, false) }}
                            </td>
                        </tr>
                        <!-- Table Row End -->
                    @endforeach
                    <!-- Table Row Start -->
                    <tr align="right" class="balance text-black">
                        <td class="balance-right">
                            {{ currency_format($proposalTemplate->total, $proposalTemplate->currency_id, false) }}
                            {{ $proposalTemplate->currency->currency_code }}</td>
                    </tr>
                    <!-- Table Row End -->

                </table>
            </td>
        </tr>

        <!-- Table Row End -->
    </table>
    <table class="bg-white" border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation">
        <tbody>
            <!-- Table Row Start -->
            <tr>
                <td height="10"></td>
            </tr>
            <tr>
                <td class="f-11">
                    @lang('modules.invoiceSettings.invoiceTerms')</td>
            </tr>
            <!-- Table Row End -->
            <!-- Table Row Start -->
            <tr class="text-grey">
                <td class="f-11 line-height">{!! nl2br($invoiceSetting->invoice_terms) !!}</td>
            </tr>
        </tbody>
    </table>

</body>

</html>
