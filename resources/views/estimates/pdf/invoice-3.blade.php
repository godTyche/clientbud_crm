<!DOCTYPE html>
<!--
  Invoice template by invoicebus.com
  To customize this template consider following this guide https://invoicebus.com/how-to-create-invoice-template/
  This template is under Invoicebus Template License, see https://invoicebus.com/templates/license/
-->
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>@lang('app.estimate')</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Invoice">
    @includeIf('estimates.pdf.estimate_pdf_css')
    <style>
        /*! Invoice Templates @author: Invoicebus @email: info@invoicebus.com @web: https://invoicebus.com @version: 1.0.0 @updated: 2015-02-27 16:02:34 @license: Invoicebus */
        /* Reset styles */
        /*@import url("https://fonts.googleapis.com/css?family=Open+Sans:400,700&subset=cyrillic,cyrillic-ext,latin,greek-ext,greek,latin-ext,vietnamese");*/

        @if ($invoiceSetting->locale != 'th')
            html,
            body,
            div,
            span,
            applet,
            object,
            iframe,
            h1,
            h2,
            h3,
            h4,
            h5,
            h6,
            p,
            blockquote,
            pre,
            a,
            abbr,
            acronym,
            address,
            big,
            cite,
            code,
            del,
            dfn,
            em,
            img,
            ins,
            kbd,
            q,
            s,
            samp,
            small,
            strike,
            strong,
            sub,
            sup,
            tt,
            var,
            b,
            u,
            i,
            center,
            dl,
            dt,
            dd,
            fieldset,
            form,
            label,
            legend,
            table,
            caption,
            tbody,
            tfoot,
            thead,
            tr,
            th,
            td,
            article,
            aside,
            canvas,
            details,
            embed,
            figure,
            figcaption,
            footer,
            header,
            hgroup,
            menu,
            nav,
            output,
            ruby,
            section,
            summary,
            time,
            mark,
            audio,
            video {
                margin: 0;
                padding: 0;
                border: 0;
                font-family: Verdana, Arial, Helvetica, sans-serif;
                vertical-align: baseline;
            }

            html {
                line-height: 1;
            }

            table {
                border-collapse: collapse;
                border-spacing: 0;
            }

            caption,
            th,
            td {
                text-align: left;
                font-weight: normal;
                vertical-align: middle;
            }

            q,
            blockquote {
                quotes: none;
            }

            q:before,
            q:after,
            blockquote:before,
            blockquote:after {
                content: "";
                content: none;
            }

            a img {
                border: none;
            }

            article,
            aside,
            details,
            figcaption,
            figure,
            footer,
            header,
            hgroup,
            main,
            menu,
            nav,
            section,
            summary {
                display: block;
            }

            /* Invoice styles */
            /**
         * DON'T override any styles for the <html> and <body> tags, as this may break the layout.
         * Instead wrap everything in one main <div id="container"> element where you may change
         * something like the font or the background of the invoice
         */
            html,
            body {
                /* MOVE ALONG, NOTHING TO CHANGE HERE! */
            }
        @endif

        /**
         * IMPORTANT NOTICE: DON'T USE '!important' otherwise this may lead to broken print layout.
         * Some browsers may require '!important' in oder to work properly but be careful with it.
         */
        .clearfix {
            display: block;
            clear: both;
        }

        .hidden {
            display: none;
        }

        b,
        strong,
        .bold {
            font-weight: bold;
        }

        .client-logo {
            float: right !important;
            height: 80px
        }

        .client-logo-div {
            position: absolute;
            right: 0;
            margin-top: -150px;
            margin-right: 20px
        }

        #container {
            font: normal 13px/1.4em 'Open Sans', Sans-serif;
            margin: 0 auto;
            color: #5B6165;
            position: relative;
        }

        #memo {
            padding-top: 40px;
            margin: 0 30px;
            border-bottom: 1px solid #ddd;
            height: 85px;
        }

        #memo .logo {
            float: left;
            margin-right: 20px;
        }

        #memo .logo img {
            height: 50px;
        }

        #memo .company-info {
            /*float: right;*/
            text-align: right;
            line-height: 18px;
        }

        #memo .company-info>div:first-child {
            line-height: 1em;
            font-size: 20px;
            color: #B32C39;
        }

        #memo .company-info span {
            font-size: 11px;
            display: inline-block;
            min-width: 20px;
            width: 100%;
        }

        #memo:after {
            content: '';
            display: block;
            clear: both;
        }

        #invoice-title-number {
            font-weight: bold;
            margin: 30px 0;
        }

        #invoice-title-number span {
            line-height: 0.88em;
            display: inline-block;
            min-width: 20px;
        }

        #invoice-title-number #title {
            text-transform: uppercase;
            padding: 8px 5px 8px 30px;
            font-size: 30px;
            background: #F4846F;
            color: white;
        }

        #invoice-title-number #number {
            margin-left: 10px;
            padding: 8px 0;
            font-size: 30px;
        }

        #client-info {
            float: left;
            margin-left: 30px;
            min-width: 220px;
            line-height: 18px;
        }

        #client-info>div {
            margin-bottom: 3px;
            min-width: 20px;
        }

        #client-info span {
            display: block;
            min-width: 20px;
        }

        #client-info>span {
            text-transform: uppercase;
        }

        table {
            table-layout: fixed;
        }

        table th,
        table td {
            vertical-align: top;
            word-break: keep-all;
            word-wrap: break-word;
        }

        #items {
            margin: 25px 30px 0 30px;
        }

        #items .first-cell,
        #items table th:first-child,
        #items table td:first-child {
            width: 40px !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            text-align: right;
        }

        #items table {
            border-collapse: separate;
            width: 100%;
        }

        #items table th {
            font-weight: bold;
            padding: 5px 8px;
            text-align: right;
            background: #B32C39;
            color: white;
            text-transform: uppercase;
        }

        #items table th:nth-child(2) {
            width: 30%;
            text-align: left;
        }

        #items table th:last-child {
            text-align: right;
        }

        #items table td {
            padding: 9px 8px;
            text-align: right;
            border-bottom: 1px solid #ddd;
        }

        #items table td:nth-child(2) {
            text-align: left;
        }

        #sums table {
            width: 50%;
            float: right;
            margin-right: 30px;
        }

        #sums table tr th,
        #sums table tr td {
            min-width: 100px;
            padding: 9px 8px;
            text-align: right;
        }

        #sums table tr th {
            width: 70%;
            font-weight: bold;
            padding-right: 35px;
        }

        #sums table tr td.last {
            min-width: 0 !important;
            max-width: 0 !important;
            width: 0 !important;
            padding: 0 !important;
            border: none !important;
        }

        #sums table tr.amount-total th {
            text-transform: uppercase;
        }

        #sums table tr.amount-total th,
        #sums table tr.amount-total td {
            font-size: 15px;
            font-weight: bold;
        }

        #invoice-info {
            margin: 10px 30px;
            line-height: 18px;
        }

        #invoice-info>div>span {
            display: inline-block;
            min-width: 20px;
            min-height: 18px;
            margin-bottom: 3px;
        }

        #invoice-info>div>span:first-child {
            color: black;
        }

        #invoice-info>div>span:last-child {
            color: #aaa;
        }

        #invoice-info:after {
            content: '';
            display: block;
            clear: both;
        }

        #terms .notes {
            min-height: 30px;
            min-width: 50px;
            margin: 0 30px;
            font-size: 11px;
        }

        #calculate_tax .calculate_tax {
            min-height: 30px;
            min-width: 50px;
            margin: 10px 0 0 30px;
        }

        #terms .payment-info div {
            margin-bottom: 3px;
            min-width: 20px;
        }

        .thank-you {
            margin: 10px 0 30px 0;
            display: inline-block;
            min-width: 20px;
            text-transform: uppercase;
            font-weight: bold;
            line-height: 0.88em;
            float: right;
            padding: 5px 30px 0 2px;
            font-size: 20px;
            background: #F4846F;
            color: white;
        }

        .ib_bottom_row_commands {
            margin-left: 30px !important;
        }

        .item-summary {
            font-size: 11px
        }

        .mb-3 {
            margin-bottom: 1rem;
        }

        .text-white {
            color: white;
        }

        /**
         * If the printed invoice is not looking as expected you may tune up
         * the print styles (you can use !important to override styles)
         */
        @media print {
            /* Here goes your print styles */
        }

        .page_break {
            page-break-before: always;
        }

        .h3-border {
            border-bottom: 1px solid #AAAAAA;
        }

        table td.text-center {
            text-align: center;
        }

        #itemsPayment,
        .box-title {
            margin: 25px 30px 0 30px;
        }

        #itemsPayment .first-cell,
        #itemsPayment table th:first-child,
        #itemsPayment table td:first-child {
            width: 40px !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            text-align: right;
        }

        #itemsPayment table {
            border-collapse: separate;
            width: 100%;
        }

        #itemsPayment table th {
            font-weight: bold;
            padding: 5px 8px;
            text-align: right;
            background: #B32C39;
            color: white;
            text-transform: uppercase;
        }

        #itemsPayment table th:nth-child(2) {
            width: 30%;
            text-align: left;
        }

        #itemsPayment table th:last-child {
            text-align: right;
        }

        #itemsPayment table td {
            padding: 9px 8px;
            text-align: right;
            border-bottom: 1px solid #ddd;
        }

        #itemsPayment table td:nth-child(2) {
            text-align: left;
        }

        table th,
        table td {
            vertical-align: top;
            word-break: keep-all;
            word-wrap: break-word;
        }

        .word-break {
            word-wrap: break-word;
        }

        .f-13 {
            font-size: 13px;
        }

        #notes {
            color: #767676;
            font-size: 11px;
            margin-top: 10px;
            margin-left: 40px;
        }

        #field-title {
            color: #504f4f;
            font-size: 13px;
            margin-top: 10px;
            margin-left: 40px;
        }

        @if ($invoiceSetting->locale == 'th')

            table td {
                font-weight: bold !important;
                font-size: 20px !important;
            }

            .description {
                font-weight: bold !important;
                font-size: 16px !important;
            }

        @endif
    </style>
</head>

<body>
    <div id="container">
        <section id="memo" class="description">
            <div class="logo description">
                <img src="{{ $invoiceSetting->logo_url }}" />
            </div>

            <div class="company-info description">
                <div class="description">
                    {{ $company->company_name }}
                </div>

                <br />

                <span>{!! nl2br($company->defaultAddress->address) !!}</span>

                <br />

                <span>{{ $company->company_phone }}</span>

                <br />

                @if ($invoiceSetting->show_gst == 'yes' && !is_null($invoiceSetting->gst_number))
                    <div>@lang('app.gstIn'): {{ $invoiceSetting->gst_number }}</div>
                @endif
            </div>

        </section>

        <section id="invoice-title-number description">

            <span id="title">{{ $estimate->estimate_number }} {{ $invoiceSetting->locale }}</span>

        </section>

        <div class="clearfix"></div>

        <section id="client-info" class="description">
            @if (
                ($estimate->client || $estimate->clientDetails) &&
                    ($estimate->client->name ||
                        $estimate->client->email ||
                        $estimate->client->mobile ||
                        $estimate->clientDetails->company_name ||
                        $estimate->clientDetails->address) &&
                    ($invoiceSetting->show_client_name == 'yes' ||
                        $invoiceSetting->show_client_email == 'yes' ||
                        $invoiceSetting->show_client_phone == 'yes' ||
                        $invoiceSetting->show_client_company_name == 'yes' ||
                        $invoiceSetting->show_client_company_address == 'yes'))
                <span class="description">@lang('modules.invoices.billedTo'):</span>

                @if ($estimate->client && $estimate->client->name && $invoiceSetting->show_client_name == 'yes')
                    <div>
                        <span class="bold description">{{ $estimate->client->name }}</span>
                    </div>
                @endif

                @if ($estimate->client && $estimate->client->email && $invoiceSetting->show_client_email == 'yes')
                    <div>
                        <span class="description">{{ $estimate->client->email }}</span>
                    </div>
                @endif

                @if ($estimate->client && $estimate->client->mobile && $invoiceSetting->show_client_phone == 'yes')
                    <div>
                        <span>
                            @if (isset($estimate->clientdetails->user->country))
                                +{{ $estimate->clientdetails->user->country->phonecode }}
                            @endif {{ $estimate->client->mobile }}
                        </span>
                    </div>
                @endif

                @if (
                    $estimate->clientDetails &&
                        $estimate->clientDetails->company_name &&
                        $invoiceSetting->show_client_company_name == 'yes')
                    <div>
                        <span>{{ $estimate->clientDetails->company_name }}</span>
                    </div>
                @endif

                @if (
                    $estimate->clientDetails &&
                        $estimate->clientDetails->address &&
                        $invoiceSetting->show_client_company_address == 'yes')
                    <div class="mb-3">
                        <b>@lang('app.address') :</b>
                        <div>{!! nl2br($estimate->clientDetails->address) !!}</div>
                    </div>
                @endif

            @endif

            @if ($invoiceSetting->show_gst == 'yes' && !is_null($estimate->client->clientDetails->gst_number))
                <div>
                    <span> @lang('app.gstIn'): {{ $estimate->client->clientDetails->gst_number }} </span>
                </div>
            @endif

            @if ($estimate->clientDetails->company_logo)
                <div class="client-logo-div">
                    <img src="{{ $estimate->clientDetails->image_url }}"
                        alt="{{ $estimate->clientDetails->company_name }}" class="client-logo" />
                </div>
            @endif
        </section>

        <div class="clearfix"></div>
        <br>

        <section id="items">

            @if ($estimate->description)
                <div class="f-13 mb-3">{!! nl2br(pdfStripTags($estimate->description)) !!}</div>
            @endif


            <table cellpadding="0" cellspacing="0">

                <tr>
                    <th>#</th> <!-- Dummy cell for the row number and row commands -->
                    <th class="description">@lang('modules.invoices.item')</th>
                    @if ($invoiceSetting->hsn_sac_code_show)
                        <th class="description">@lang('app.hsnSac')</th>
                    @endif
                    <th class="description">@lang('modules.invoices.qty')</th>
                    <th class="description">@lang('modules.invoices.unitPrice')</th>
                    <th class="description">@lang('modules.invoices.tax')</th>
                    <th class="description">@lang('modules.invoices.price') ({!! htmlentities($estimate->currency->currency_code) !!})</th>
                </tr>

                <?php $count = 0; ?>
                @foreach ($estimate->items as $item)
                    @if ($item->type == 'item')
                        <tr data-iterate="item">
                            <td>{{ ++$count }}</td>
                            <!-- Don't remove this column as it's needed for the row commands -->
                            <td>
                                <div class="mb-3">{{ $item->item_name }}</div>
                                @if (!is_null($item->item_summary))
                                    <p class="item-summary mb-3">{!! nl2br(pdfStripTags($item->item_summary)) !!}</p>
                                @endif
                                @if ($item->estimateItemImage)
                                    <p class="mt-2">
                                        <img src="{{ $item->estimateItemImage->file_url }}" width="60"
                                            height="60" class="img-thumbnail">
                                    </p>
                                @endif
                            </td>
                            @if ($invoiceSetting->hsn_sac_code_show)
                                <td>{{ $item->hsn_sac_code ? $item->hsn_sac_code : '--' }}</td>
                            @endif
                            <td>{{ $item->quantity }}@if($item->unit)<br><span class="f-11 text-dark-grey">{{ $item->unit->unit_type }}</span>@endif</td>
                            <td>{{ currency_format($item->unit_price, $estimate->currency_id, false) }}</td>
                            <td>{{ $item->tax_list }}</td>
                            <td>{{ currency_format($item->amount, $estimate->currency_id, false) }}</td>
                        </tr>
                    @endif
                @endforeach

            </table>

        </section>

        <section id="sums" class="description">

            <table cellpadding="0" cellspacing="0">
                <tr>
                    <th>@lang('modules.invoices.subTotal'):</th>
                    <td>{{ currency_format($estimate->sub_total, $estimate->currency_id, false) }}</td>
                </tr>
                @if ($discount != 0 && $discount != '')
                    <tr data-iterate="tax">
                        <th>@lang('modules.invoices.discount'):</th>
                        <td>{{ currency_format($discount, $estimate->currency_id, false) }}</td>
                    </tr>
                @endif
                @foreach ($taxes as $key => $tax)
                    <tr data-iterate="tax">
                        <th>{{ $key }}:</th>
                        <td>{{ currency_format($tax, $estimate->currency_id, false) }}</td>
                    </tr>
                @endforeach
                <tr class="amount-total">
                    <th>@lang('modules.invoices.total'):</th>
                    <td>{{ currency_format($estimate->total, $estimate->currency_id, false) }} {!! htmlentities($estimate->currency->currency_code) !!}
                    </td>
                </tr>
            </table>

        </section>

        <div class="clearfix"></div>
        <br>


        <section id="terms" class="description">
            <div class="notes description">
                <div>
                    <span>@lang('modules.estimates.validTill'):</span>
                    <span>{{ $estimate->valid_till ? $estimate->valid_till->translatedFormat($company->date_format) : '--' }}</span>
                </div>
                @if ($estimate->status == 'unpaid')
                    <div>
                        <span>@lang('app.dueDate'):</span>
                        <span>{{ $estimate->due_date->translatedFormat($company->date_format) }}</span>
                    </div>
                @endif
                <div>
                    <span>@lang('app.status'):</span> <span>{{ $estimate->status }}</span>
                </div>

                @if ($estimate->note)
                    <br> @lang('app.note') : <br>{!! nl2br($estimate->note) !!} <br>
                @endif
                <br>@lang('modules.invoiceSettings.invoiceTerms') <br>{!! nl2br($invoiceSetting->invoice_terms) !!}<br>
                <br>
                @if (isset($invoiceSetting->other_info))
                    {!! nl2br($invoiceSetting->other_info) !!}<br>
                @endif
                @if (isset($taxes) && $invoiceSetting->tax_calculation_msg == 1)
                    <p class="text-dark-grey mt-2 description">
                        @if ($estimate->calculate_tax == 'after_discount')
                            @lang('messages.calculateTaxAfterDiscount')
                        @else
                            @lang('messages.calculateTaxBeforeDiscount')
                        @endif
                    </p>
                @endif
                <br>
                <br>
                <div class="mt-2">
                    @if ($estimate->sign)
                        <h5 style="margin-bottom: 20px;">@lang('app.signature')</h5>
                        <img src="{{ $estimate->sign->signature }}" style="height: 75px;">
                        <p>({{ $estimate->sign->full_name }})</p>
                    @endif
                </div>
            </div>
        </section>

        <div class="clearfix"></div>

        {{-- Custom fields data --}}
        @if (isset($fields) && count($fields) > 0)
            <div class="page_break"></div>
            <h3 class="box-title m-t-20 text-center h3-border"> @lang('modules.projects.otherInfo')</h3>
            <table style="background: none" border="0" cellspacing="0" cellpadding="0" width="100%">
                @foreach ($fields as $field)
                    <tr>
                        <td style="text-align: left;background: none;">
                            <div id="field-title">{{ $field->label }}</div>
                            <p id="notes" class="description">
                                @if ($field->type == 'text' || $field->type == 'password' || $field->type == 'number' || $field->type == 'textarea')
                                    {{ $estimate->custom_fields_data['field_' . $field->id] ?? '-' }}
                                @elseif($field->type == 'radio')
                                    {{ !is_null($estimate->custom_fields_data['field_' . $field->id]) ? $estimate->custom_fields_data['field_' . $field->id] : '-' }}
                                @elseif($field->type == 'select')
                                    {{ !is_null($estimate->custom_fields_data['field_' . $field->id]) && $estimate->custom_fields_data['field_' . $field->id] != '' ? $field->values[$estimate->custom_fields_data['field_' . $field->id]] : '-' }}
                                @elseif($field->type == 'checkbox')
                                    {{ !is_null($estimate->custom_fields_data['field_' . $field->id]) ? $estimate->custom_fields_data['field_' . $field->id] : '-' }}
                                @elseif($field->type == 'date')
                                    {{ !is_null($estimate->custom_fields_data['field_' . $field->id]) ? \Carbon\Carbon::parse($estimate->custom_fields_data['field_' . $field->id])->translatedFormat($estimate->company->date_format) : '--' }}
                                @endif
                            </p>
                        </td>
                    </tr>
                @endforeach
            </table>
        @endif

    </div>
</body>

</html>
