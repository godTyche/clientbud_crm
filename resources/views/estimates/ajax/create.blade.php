@php
    $addProductPermission = user()->permission('add_product');
@endphp

@if (!in_array('clients', user_modules()))
    <x-alert class="mb-3" type="danger" icon="exclamation-circle"><span>@lang('messages.enableClientModule')</span>
        <x-forms.link-secondary icon="arrow-left" :link="route('estimates.index')">@lang('app.back')</x-forms.link-secondary>
    </x-alert>
@else
<!-- CREATE INVOICE START -->
<div class="bg-white rounded b-shadow-4 create-inv">
    <!-- HEADING START -->
    <div class="px-lg-4 px-md-4 px-3 py-3">
        <h4 class="mb-0 f-21 font-weight-normal text-capitalize">@lang('app.estimateDetails')</h4>
    </div>
    <!-- HEADING END -->
    <hr class="m-0 border-top-grey">
    <!-- FORM START -->
    <x-form class="c-inv-form" id="saveInvoiceForm">
        <!-- INVOICE NUMBER, DATE, DUE DATE, FREQUENCY START -->
        <div class="row px-lg-4 px-md-4 px-3 py-3">
            <!-- INVOICE NUMBER START -->
            <div class="col-md-6 col-lg-4">
                <div class="form-group mb-4">
                    <label class="f-14 text-dark-grey mb-12 text-capitalize" for="usr">@lang('modules.estimates.estimatesNumber')</label>
                    <div class="input-group">
                        <div class="input-group-prepend  height-35 ">
                            <span class="input-group-text border-grey f-15 bg-additional-grey px-3 text-dark"
                                id="basic-addon1">{{ $invoiceSetting->estimate_prefix }}{{ $invoiceSetting->estimate_number_separator }}{{ $zero }}</span>
                        </div>
                        <input type="text" name="estimate_number" id="estimate_number"
                            class="form-control height-35 f-15"
                            value="@if (is_null($lastEstimate)) 1 @else{{ $lastEstimate }} @endif"
                            placeholder="0019" aria-label="0019" aria-describedby="basic-addon1">
                    </div>
                </div>
            </div>
            <!-- INVOICE NUMBER END -->
            <!-- INVOICE DATE START -->
            <div class="col-md-6 col-lg-4">
                <div class="form-group mb-4">
                    <x-forms.label fieldId="due_date" :fieldLabel="__('modules.estimates.validTill')" fieldRequired="true">
                    </x-forms.label>
                    <div class="input-group">
                        <input type="text" id="valid_till" name="valid_till"
                            class="px-6 position-relative text-dark font-weight-normal form-control height-35 rounded p-0 text-left f-15"
                            placeholder="@lang('placeholders.date')"
                            value="{{ Carbon\Carbon::today()->addDays(30)->format(company()->date_format) }}">
                    </div>
                </div>
            </div>
            <!-- INVOICE DATE END -->

            <!-- FREQUENCY START -->
            <div class="col-md-6 col-lg-4">
                <div class="form-group c-inv-select mb-4">
                    <x-forms.label fieldId="currency_id" :fieldLabel="__('modules.invoices.currency')">
                    </x-forms.label>

                    <div class="select-others height-35 rounded">
                        <select class="form-control select-picker" name="currency_id" id="currency_id">
                            @foreach ($currencies as $currency)
                                <option
                                    @if (isset($estimate)) @if ($currency->id == $estimate->currency_id) selected @endif
                                @else @if ($currency->id == company()->currency_id) selected @endif
                                    @if ($estimateTemplate && $currency->id == $estimateTemplate->currency_id) selected @endif @endif
                                    value="{{ $currency->id }}">
                                    {{ $currency->currency_code . ' (' . $currency->currency_symbol . ')' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <!-- FREQUENCY END -->

            <!-- CLIENT START -->
            <input type="hidden" name="template_id" value="{{ $estimateTemplate->id ?? '' }}">
            @if (isset($estimate))
                <input type="hidden" name="estimate_id" value="{{ $estimate->id ?? '' }}">
            @endif
            <div class="col-md-6 col-lg-4">
                @if (isset($client) && !is_null($client))
                    <div class="form-group mb-lg-0 mb-md-0 mb-4">
                        <x-forms.label fieldId="due_date" :fieldLabel="__('app.client')">
                        </x-forms.label>
                        <div class="input-group">
                            <input type="hidden" name="client_id" id="client_id" value="{{ $client->id }}">
                            <input type="text" value="{{ $client->name }}"
                                class="form-control height-35 f-15 readonly-background" readonly>
                        </div>
                    </div>
                @else
                    <x-client-selection-dropdown :clients="$clients" :selected="isset($estimate)
                    ? $estimate->client_id
                    : (request()->has('default_client')
                        ? request()->has('default_client')
                        : null)" />
                @endif
            </div>
            <!-- CLIENT END -->

            <div class="col-md-6 col-lg-4">
                <div class="form-group c-inv-select mb-4">
                    <x-forms.label fieldId="calculate_tax" :fieldLabel="__('modules.invoices.calculateTax')">
                    </x-forms.label>
                    <div class="select-others height-35 rounded">
                        <select class="form-control select-picker" data-live-search="true" data-size="8"
                            name="calculate_tax" id="calculate_tax">
                            <option value="after_discount">@lang('modules.invoices.afterDiscount')</option>
                            <option value="before_discount" @if (isset($estimateTemplate->calculate_tax) && $estimateTemplate->calculate_tax == 'before_discount') selected @endif>
                                @lang('modules.invoices.beforeDiscount')</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="col-md-12 my-3">
                <div class="form-group">
                    <x-forms.label fieldId="description" :fieldLabel="__('app.description')">
                    </x-forms.label>
                    <div id="description">{!! isset($estimate) ? $estimate->description : ($estimateTemplate ? $estimateTemplate->description : '') !!}</div>
                    <textarea name="description" id="description-text" class="d-none"></textarea>
                </div>
            </div>

        </div>
        <!-- INVOICE NUMBER, DATE, DUE DATE, FREQUENCY END -->

        <x-forms.custom-field :fields="$fields"></x-forms.custom-field>

        <hr class="m-0 border-top-grey">

        <div class="row px-lg-4 px-md-4 px-3 py-3">
            <div class="col-md-3 d-none product-category-filter">
                <div class="form-group c-inv-select mb-4">
                    <x-forms.input-group>
                        <select class="form-control select-picker" name="category_id"
                                id="product_category_id" data-live-search="true">
                            <option value="">{{  __('app.menu.selectProductCategory')  }}</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">
                                    {{ $category->category_name }}</option>
                            @endforeach
                        </select>
                    </x-forms.input-group>
                </div>
            </div>
            @if(in_array('products', user_modules()) || in_array('purchase', user_modules()))
                <div class="col-md-3">
                    <div class="form-group c-inv-select mb-4">
                    <x-forms.input-group>
                        <select class="form-control select-picker" data-live-search="true" data-size="8" id="add-products" title="{{ __('app.menu.selectProduct') }}">
                            @foreach ($products as $item)
                                <option data-content="{{ $item->name }}" value="{{ $item->id }}">
                                    {{ $item->name }}</option>
                            @endforeach
                        </select>
                        <x-slot name="preappend">
                            <a href="javascript:;"
                                class="btn btn-outline-secondary border-grey toggle-product-category"
                                data-toggle="tooltip" data-original-title="{{ __('modules.productCategory.filterByCategory') }}"><i class="fa fa-filter"></i></a>
                        </x-slot>
                        @if ($addProductPermission == 'all' || $addProductPermission == 'added')
                            <x-slot name="append">
                                <a href="{{ route('products.create') }}" data-redirect-url="no"
                                    class="btn btn-outline-secondary border-grey openRightModal"
                                    data-toggle="tooltip" data-original-title="{{ __('modules.dashboard.addNewProduct') }}">@lang('app.add')</a>
                            </x-slot>
                        @endif
                    </x-forms.input-group>
                    </div>
                </div>
            @endif
        </div>

        <div id="sortable">
            @if (isset($estimate))
                @foreach ($estimate->items as $key => $item)
                    <!-- DESKTOP DESCRIPTION TABLE START -->
                    <div class="d-flex px-4 py-3 c-inv-desc item-row">

                        <div class="c-inv-desc-table w-100 d-lg-flex d-md-flex d-block">
                            <table width="100%">
                                <tbody>
                                    <tr class="text-dark-grey font-weight-bold f-14">
                                        <td width="{{ $invoiceSetting->hsn_sac_code_show ? '40%' : '50%' }}"
                                            class="border-0 inv-desc-mbl btlr">@lang('app.description')</td>
                                        @if ($invoiceSetting->hsn_sac_code_show)
                                            <td width="10%" class="border-0" align="right">@lang('app.hsnSac')
                                            </td>
                                        @endif
                                        <td width="10%" class="border-0" align="right">
                                            @lang('modules.invoices.qty')
                                        </td>
                                        <td width="10%" class="border-0" align="right">
                                            @lang('modules.invoices.unitPrice')</td>
                                        <td width="13%" class="border-0" align="right">
                                            @lang('modules.invoices.tax')
                                        </td>
                                        <td width="17%" class="border-0 bblr-mbl" align="right">
                                            @lang('modules.invoices.amount')</td>
                                    </tr>
                                    <tr>
                                        <td class="border-bottom-0 btrr-mbl btlr">
                                            <input type="text" class="f-14 border-0 w-100 item_name form-control"
                                                name="item_name[]" placeholder="@lang('modules.expenses.itemName')"
                                                value="{{ $item->item_name }}">
                                        </td>
                                        <td class="border-bottom-0 d-block d-lg-none d-md-none">
                                            <textarea class="f-14 border-0 w-100 mobile-description form-control" placeholder="@lang('placeholders.invoices.description')"
                                                name="item_summary[]">{{ $item->item_summary }}</textarea>
                                        </td>
                                        @if ($invoiceSetting->hsn_sac_code_show)
                                            <td class="border-bottom-0">
                                                <input type="text" min="1"
                                                    class="f-14 border-0 w-100 text-right hsn_sac_code form-control"
                                                    value="{{ $item->hsn_sac_code }}" name="hsn_sac_code[]">
                                            </td>
                                        @endif
                                        <td class="border-bottom-0">
                                            <input type="number" min="1"
                                                class="form-control f-14 border-0 w-100 text-right quantity"
                                                value="{{ $item->quantity }}" name="quantity[]">
                                            @if (!is_null($item->product_id) && $item->product_id != 0)
                                                <span class="text-dark-grey float-right border-0 f-12">{{ $item->unit->unit_type }}</span>
                                                <input type="hidden" name="product_id[]" value="{{ $item->product_id }}">
                                                <input type="hidden" name="unit_id[]" value="{{ $item->unit_id }}">
                                            @else
                                                <select class="text-dark-grey float-right border-0 f-12" name="unit_id[]">
                                                    @foreach ($units as $unit)
                                                        <option
                                                        @if ($item->unit_id == $unit->id) selected @endif
                                                        value="{{ $unit->id }}">{{ $unit->unit_type }}</option>
                                                    @endforeach
                                                </select>
                                                <input type="hidden" name="product_id[]" value="">
                                            @endif
                                        </td>
                                        <td class="border-bottom-0">
                                            <input type="number" min="1"
                                                class="f-14 border-0 w-100 text-right cost_per_item form-control"
                                                placeholder="0.00" value="{{ $item->unit_price }}"
                                                name="cost_per_item[]">
                                        </td>
                                        <td class="border-bottom-0">
                                            <div class="select-others height-35 rounded border-0">
                                                <select id="multiselect" name="taxes[{{ $key }}][]"
                                                    multiple="multiple"
                                                    class="select-picker type customSequence border-0" data-size="3">
                                                    @foreach ($taxes as $tax)
                                                        <option data-rate="{{ $tax->rate_percent }}" data-tax-text="{{ $tax->tax_name .':'. $tax->rate_percent }}%"
                                                            @if (isset($item->taxes) && array_search($tax->id, json_decode($item->taxes)) !== false) selected @endif
                                                            value="{{ $tax->id }}">
                                                            {{ $tax->tax_name }}:
                                                            {{ $tax->rate_percent }}%</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </td>
                                        <td rowspan="2" align="right" valign="top"
                                            class="bg-amt-grey btrr-bbrr">
                                            <span
                                                class="amount-html">{{ number_format((float) $item->amount, 2, '.', '') }}</span>
                                            <input type="hidden" class="amount" name="amount[]"
                                                value="{{ $item->amount }}">
                                        </td>
                                    </tr>
                                    <tr class="d-none d-md-block d-lg-table-row">
                                        <td colspan="{{ $invoiceSetting->hsn_sac_code_show ? '4' : '3' }}"
                                            class="dash-border-top bblr">
                                            <textarea class="f-14 border-0 w-100 desktop-description form-control" name="item_summary[]"
                                                placeholder="@lang('placeholders.invoices.description')">{{ $item->item_summary }}</textarea>
                                        </td>
                                        <td class="border-left-0">
                                            <input type="hidden" id="imageId_{{ $item->id }}"
                                                class="itemOldImage" name="image_id[]"
                                                value={{ isset($item->estimateItemImage->id) ? $item->estimateItemImage->id : '' }} />

                                            <input type="file" class="dropify itemImage"
                                                name="invoice_item_image[]" id="image{{ $item->id }}"
                                                data-index="{{ $loop->index }}"
                                                data-allowed-file-extensions="png jpg jpeg bmp"
                                                data-item-id="{{ $item->id }}"
                                                data-default-file="{{ $item->estimateItemImage ? $item->estimateItemImage->file_url : '' }}"
                                                data-height="70" multiple />
                                            <input type="hidden" name="invoice_item_image_url[]">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <a href="javascript:;"
                                class="d-flex align-items-center justify-content-center ml-3 remove-item"><i
                                    class="fa fa-times-circle f-20 text-lightest"></i></a>
                        </div>
                    </div>
                    <!-- DESKTOP DESCRIPTION TABLE END -->
                @endforeach
            @elseif (isset($estimateTemplateItem) && !is_null($estimateTemplateItem))

                @foreach ($estimateTemplateItem as $item)
                    <!-- DESKTOP DESCRIPTION TABLE START -->
                    <div class="d-flex px-4 py-3 c-inv-desc item-row">
                        <div class="c-inv-desc-table w-100 d-lg-flex d-md-flex d-block">
                            <table width="100%">
                                <tbody>
                                    <tr class="text-dark-grey font-weight-bold f-14">
                                        <td width="40%" class="border-0 inv-desc-mbl btlr">@lang('app.description')</td>
                                        @if ($invoiceSetting->hsn_sac_code_show)
                                            <td width="10%" class="border-0" align="right">@lang('app.hsnSac')
                                            </td>
                                        @endif
                                        <td width="10%" class="border-0" align="right">
                                            @lang('modules.invoices.qty')
                                        </td>
                                        <td width="10%" class="border-0" align="right">
                                            @lang('modules.invoices.unitPrice')
                                        </td>
                                        <td width="13%" class="border-0" align="right">@lang('modules.invoices.tax')
                                        </td>
                                        <td width="17%" class="border-0 bblr-mbl" align="right">
                                            @lang('modules.invoices.amount')</td>
                                    </tr>
                                    <tr>
                                        <td class="border-bottom-0 btrr-mbl btlr">
                                            <input type="text" class="f-14 border-0 w-100 item_name form-control"
                                                name="item_name[]" placeholder="@lang('modules.expenses.itemName')"
                                                value="{{ $item->item_name }}">
                                        </td>
                                        @if ($invoiceSetting->hsn_sac_code_show)
                                            <td class="border-bottom-0">
                                                <input type="text"
                                                    class="f-14 border-0 w-100 text-right hsn_sac_code form-control"
                                                    value="{{ $item->hsn_sac_code }}" name="hsn_sac_code[]">
                                            </td>
                                        @endif
                                        <td class="border-bottom-0">
                                            <input type="number" min="1"
                                                class="f-14 border-0 w-100 text-right quantity form-control"
                                                name="quantity[]" value="{{ $item->quantity }}">
                                            @if (!is_null($item->product_id) && $item->product_id != 0)
                                                <span class="text-dark-grey float-right border-0 f-12">{{ $item->unit->unit_type }}</span>
                                                <input type="hidden" name="product_id[]" value="{{ $item->product_id }}">
                                                <input type="hidden" name="unit_id[]" value="{{ $item->unit_id }}">
                                            @else
                                                <select class="text-dark-grey float-right border-0 f-12" name="unit_id[]">
                                                    @foreach ($units as $unit)
                                                        <option
                                                        @if ($item->unit_id == $unit->id) selected @endif
                                                        value="{{ $unit->id }}">{{ $unit->unit_type }}</option>
                                                    @endforeach
                                                </select>
                                                <input type="hidden" name="product_id[]" value="">
                                            @endif
                                        </td>
                                        <td class="border-bottom-0">
                                            <input type="number" min="1"
                                                class="f-14 border-0 w-100 text-right cost_per_item form-control"
                                                placeholder="0.00" name="cost_per_item[]"
                                                value="{{ $item->unit_price }}">
                                        </td>
                                        <td class="border-bottom-0">
                                            <div class="select-others height-35 rounded border-0">
                                                <select id="multiselect" name="taxes[0][]" multiple="multiple"
                                                    class="select-picker type customSequence border-0" data-size="3">
                                                    @foreach ($taxes as $tax)
                                                        <option data-rate="{{ $tax->rate_percent }}" data-tax-text="{{ $tax->tax_name .':'. $tax->rate_percent }}%"
                                                            @if (isset($item->taxes) && array_search($tax->id, json_decode($item->taxes)) !== false) selected @endif
                                                            value="{{ $tax->id }}">
                                                            {{ $tax->tax_name }}
                                                            {{ $tax->rate_percent }}%</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </td>
                                        <td rowspan="2" align="right" valign="top"
                                            class="bg-amt-grey btrr-bbrr">
                                            <span
                                                class="amount-html">{{ number_format((float) $item->amount, 2, '.', '') }}</span>
                                            <input type="hidden" class="amount" name="amount[]"
                                                value="{{ $item->amount }}">
                                        </td>
                                    </tr>
                                    <tr class="d-none d-md-table-row d-lg-table-row">
                                        <td colspan="{{ $invoiceSetting->hsn_sac_code_show ? '4' : '3' }}"
                                            class="dash-border-top bblr">
                                            <textarea class="f-14 border-0 w-100 desktop-description form-control" name="item_summary[]"
                                                placeholder="@lang('placeholders.invoices.description')">{{ $item->item_summary }}</textarea>
                                        </td>
                                        <td class="border-left-0">
                                            <input type="hidden" id="imageId_{{ $item->id }}"
                                            class="itemOldImage" name="templateImage_id[]"
                                            value={{ isset($item->estimateTemplateItemImage->id) ? $item->estimateTemplateItemImage->id : '' }} />

                                            <input type="file" class="dropify" name="invoice_item_image[]"
                                                data-allowed-file-extensions="png jpg jpeg bmp"
                                                data-messages-default="test" data-height="70"
                                                data-id="{{ $item->id }}" id="{{ $item->id }}"
                                                data-default-file="{{ $item->estimateTemplateItemImage ? $item->estimateTemplateItemImage->file_url : '' }}"
                                                @if ($item->estimateTemplateItemImage && $item->estimateTemplateItemImage->external_link) data-show-remove="false" @endif />
                                            <input type="hidden" name="invoice_item_image_url[]"
                                                value="{{ $item->estimateTemplateItemImage ? $item->estimateTemplateItemImage->file : '' }}">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <a href="javascript:;"
                                class="d-flex align-items-center justify-content-center ml-3 remove-item"><i
                                    class="fa fa-times-circle f-20 text-lightest"></i></a>
                        </div>
                    </div>
                    <!-- DESKTOP DESCRIPTION TABLE END -->
                @endforeach
            @else
                <!-- DESKTOP DESCRIPTION TABLE START -->

                <div class="d-flex px-4 py-3 c-inv-desc item-row">

                    <div class="c-inv-desc-table w-100 d-lg-flex d-md-flex d-block">
                        <table width="100%">
                            <tbody>
                                <tr class="text-dark-grey font-weight-bold f-14">
                                    <td width="{{ $invoiceSetting->hsn_sac_code_show ? '40%' : '50%' }}"
                                        class="border-0 inv-desc-mbl btlr">@lang('app.description')</td>
                                    @if ($invoiceSetting->hsn_sac_code_show)
                                        <td width="10%" class="border-0" align="right">@lang('app.hsnSac')</td>
                                    @endif
                                    <td width="10%" class="border-0" align="right">
                                        @lang('modules.invoices.qty')
                                    </td>
                                    <td width="10%" class="border-0" align="right">
                                        @lang('modules.invoices.unitPrice')
                                    </td>
                                    <td width="13%" class="border-0" align="right">@lang('modules.invoices.tax')
                                    </td>
                                    <td width="17%" class="border-0 bblr-mbl" align="right">
                                        @lang('modules.invoices.amount')</td>
                                </tr>
                                <tr>
                                    <td class="border-bottom-0 btrr-mbl btlr">
                                        <input type="text" class="f-14 border-0 w-100 item_name form-control"
                                            name="item_name[]" placeholder="@lang('modules.expenses.itemName')">
                                    </td>
                                    <td class="border-bottom-0 d-block d-lg-none d-md-none">
                                        <textarea class="form-control f-14 border-0 w-100 mobile-description" name="item_summary[]"
                                            placeholder="@lang('placeholders.invoices.description')"></textarea>
                                    </td>
                                    @if ($invoiceSetting->hsn_sac_code_show)
                                        <td class="border-bottom-0">
                                            <input type="text" min="1"
                                                class="f-14 border-0 w-100 text-right hsn_sac_code form-control"
                                                value="" name="hsn_sac_code[]">
                                        </td>
                                    @endif
                                    <td class="border-bottom-0">
                                        <input type="number" min="1"
                                            class="form-control f-14 border-0 w-100 text-right quantity mt-3"
                                            value="1" name="quantity[]">
                                            <select class="text-dark-grey float-right border-0 f-12" name="unit_id[]">
                                                @foreach ($units as $unit)
                                                    <option
                                                    @if ($unit->default == 1) selected @endif
                                                    value="{{ $unit->id }}">{{ $unit->unit_type }}</option>
                                                @endforeach
                                            </select>
                                            <input type="hidden" name="product_id[]" value="">
                                    </td>
                                    <td class="border-bottom-0">
                                        <input type="number" min="1"
                                            class="f-14 border-0 w-100 text-right cost_per_item form-control"
                                            placeholder="0.00" value="0" name="cost_per_item[]">
                                    </td>
                                    <td class="border-bottom-0">
                                        <div class="select-others height-35 rounded border-0">
                                            <select id="multiselect" name="taxes[0][]" multiple="multiple"
                                                class="select-picker type customSequence border-0" data-size="3">
                                                @foreach ($taxes as $tax)
                                                    <option data-rate="{{ $tax->rate_percent }}" data-tax-text="{{ $tax->tax_name .':'. $tax->rate_percent }}%"
                                                        value="{{ $tax->id }}">{{ $tax->tax_name }}:
                                                        {{ $tax->rate_percent }}%</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </td>
                                    <td rowspan="2" align="right" valign="top" class="bg-amt-grey btrr-bbrr">
                                        <span class="amount-html">0.00</span>
                                        <input type="hidden" class="amount" name="amount[]" value="0">
                                    </td>
                                </tr>
                                <tr class="d-none d-md-table-row d-lg-table-row">
                                    <td colspan="{{ $invoiceSetting->hsn_sac_code_show ? '4' : '3' }}"
                                        class="dash-border-top bblr">
                                        <textarea class="f-14 border-0 w-100 desktop-description form-control" name="item_summary[]"
                                            placeholder="@lang('placeholders.invoices.description')"></textarea>
                                    </td>
                                    <td class="border-left-0">
                                        <input type="file" class="dropify" name="invoice_item_image[]"
                                            data-allowed-file-extensions="png jpg jpeg bmp" data-height="70" />
                                        <input type="hidden" name="invoice_item_image_url[]">
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <a href="javascript:;"
                            class="d-flex align-items-center justify-content-center ml-3 remove-item"><i
                                class="fa fa-times-circle f-20 text-lightest"></i></a>
                    </div>
                </div>
                <!-- DESKTOP DESCRIPTION TABLE END -->
            @endif

        </div>
        <!--  ADD ITEM START-->
        <div class="row px-lg-4 px-md-4 px-3 pb-3 pt-0 mb-3  mt-2">
            <div class="col-md-12">
                <a class="f-15 f-w-500" href="javascript:;" id="add-item"><i
                        class="icons icon-plus font-weight-bold mr-1"></i>@lang('modules.invoices.addItem')</a>
            </div>
        </div>
        <!--  ADD ITEM END-->

        <hr class="m-0 border-top-grey">

        <!-- TOTAL, DISCOUNT START -->
        <div class="d-flex px-lg-4 px-md-4 px-3 pb-3 c-inv-total">
            <table width="100%" class="text-right f-14 text-capitalize">
                <tbody>
                    <tr>
                        <td width="50%" class="border-0 d-lg-table d-md-table d-none"></td>
                        <td width="50%" class="p-0 border-0 c-inv-total-right">
                            <table width="100%">
                                <tbody>
                                    <tr>
                                        <td colspan="2" class="border-top-0 text-dark-grey">
                                            @lang('modules.invoices.subTotal')</td>
                                        <td width="30%" class="border-top-0 sub-total">0.00</td>
                                        <input type="hidden" class="sub-total-field" name="sub_total"
                                            value="0">
                                    </tr>
                                    <tr>
                                        <td width="20%" class="text-dark-grey">@lang('modules.invoices.discount')
                                        </td>
                                        <td width="40%" style="padding: 5px;">
                                            <table width="100%" class="mw-250">
                                                <tbody>
                                                    <tr>
                                                        <td width="70%" class="c-inv-sub-padding">
                                                            <input type="number" min="0"
                                                                name="discount_value"
                                                                class="form-control f-14 border-0 w-100 text-right discount_value"
                                                                placeholder="0"
                                                                {{ isset($estimteTemplate) ? $estimateTemplate->discount : '0' }}
                                                                </td>
                                                        <td width="30%" align="left" class="c-inv-sub-padding">
                                                            <div
                                                                class="select-others select-tax height-35 rounded border-0">
                                                                <select class="form-control select-picker"
                                                                    id="discount_type" name="discount_type">
                                                                    <option
                                                                        @if (isset($estimateTemplate) && $estimateTemplate->discount_type == 'percent') selected @endif
                                                                        value="percent">%
                                                                    </option>
                                                                    <option
                                                                        @if (isset($estimateTemplate) && $estimateTemplate->discount_type == 'fixed') selected @endif
                                                                        value="fixed">
                                                                        @lang('modules.invoices.amount')</option>
                                                                </select>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                        <td><span
                                                id="discount_amount">@if(isset($estimate)) {{ number_format((float) $estimate->discount, 2, '.', '') }} @else 0.00 @endif </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>@lang('modules.invoices.tax')</td>
                                        <td colspan="2" class="p-0 border-0">
                                            <table width="100%" id="invoice-taxes">
                                                <tr>
                                                    <td colspan="2"><span class="tax-percent">0.00</span></td>
                                                </tr>
                                            </table>
                                        </td>

                                    </tr>
                                    <tr class="bg-amt-grey f-16 f-w-500">
                                        <td colspan="2">@lang('modules.invoices.total')</td>
                                        <td><span class="total">0.00</span></td>
                                        <input type="hidden" class="total-field" name="total" value="0">
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!-- TOTAL, DISCOUNT END -->

        <!-- NOTE AND TERMS AND CONDITIONS START -->
        <div class="d-flex flex-wrap px-lg-4 px-md-4 px-3 py-3">
            <div class="col-md-6 col-sm-12 c-inv-note-terms p-0 mb-lg-0 mb-md-0 mb-3">
                <x-forms.label fieldId="" class="text-capitalize" :fieldLabel="__('modules.invoices.note')">
                </x-forms.label>
                <textarea class="form-control" name="note" id="note" rows="4" placeholder="@lang('placeholders.invoices.note')"></textarea>
            </div>
            <div class="col-md-6 col-sm-12 p-0 c-inv-note-terms">
                <x-forms.label fieldId="" :fieldLabel="__('modules.invoiceSettings.invoiceTerms')">
                </x-forms.label>
                <p>
                    {!! nl2br($invoiceSetting->invoice_terms) !!}
                </p>
            </div>
        </div>
        <!-- NOTE AND TERMS AND CONDITIONS END -->

        <!-- CANCEL SAVE SEND START -->
        <x-form-actions class="c-inv-btns">
            <div class="d-flex mb-3">

                <div class="inv-action dropup mr-3">
                    <button class="btn-primary dropdown-toggle" type="button" data-toggle="dropdown"
                        aria-haspopup="true" aria-expanded="false">
                        @lang('app.save')
                        <span><i class="fa fa-chevron-down f-15 text-white"></i></span>
                    </button>
                    <!-- DROPDOWN - INFORMATION -->
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuBtn" tabindex="0">
                        <li>
                            <a class="dropdown-item f-14 text-dark save-form" href="javascript:;" data-type="save">
                                <i class="fa fa-save f-w-500 mr-2 f-11"></i> @lang('app.save')
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item f-14 text-dark save-form" href="javascript:void(0);"
                                data-type="send">
                                <i class="fa fa-paper-plane f-w-500  mr-2 f-12"></i> @lang('app.saveSend')
                            </a>
                        </li>
                    </ul>
                </div>

                <x-forms.button-secondary data-type="draft" class="save-form mr-3">@lang('app.saveDraft')
                </x-forms.button-secondary>

            </div>

            <x-forms.button-cancel :link="route('estimates.index')" class="border-0">@lang('app.cancel')
            </x-forms.button-cancel>

        </x-form-actions>
        <!-- CANCEL SAVE SEND END -->

    </x-form>
    <!-- FORM END -->
</div>
<!-- CREATE INVOICE END -->

<script>
    $(document).ready(function() {

        $('.toggle-product-category').click(function() {
            $('.product-category-filter').toggleClass('d-none');
        });

        $('#product_category_id').on('change', function(){
            var categoryId = $(this).val();
            var url = "{{route('invoices.product_category', ':id')}}",
            url = (categoryId) ? url.replace(':id', categoryId) : url.replace(':id', null);;
            $.easyAjax({
                url : url,
                type : "GET",
                container: '#saveInvoiceForm',
                blockUI: true,
                success: function (response) {
                    if (response.status == 'success') {
                        var options = [];
                        var rData = [];
                        rData = response.data;
                        $.each(rData, function(index, value) {
                            var selectData = '';
                            selectData = '<option value="' + value.id + '">' + value.name +
                                '</option>';
                            options.push(selectData);
                        });
                        $('#add-products').html(
                            '<option value="" class="form-control" >{{  __('app.menu.selectProduct') }}</option>' +
                            options);
                        $('#add-products').selectpicker('refresh');
                    }
                }
            });
        });

        $('.itemImage').on('change', function(e) {
            console.log($('#' + e.target.id).data('item-id'));
            $('#imageId_' + $('#' + e.target.id).data('item-id')).val('');

        })

        $(".itemOldImage").next(".dropify-clear").trigger("click");
        var file = $('#sortable .dropify').dropify({
            messages: dropifyMessages
        });


        file.on("dropify.afterClear", function(event, element) {
            var elementID = element.element.id;
            var elementName = element.element.name;
            var elementIndex = element.element.dataset.index;
            if (elementName.indexOf("[]") > -1) {
                elementName = elementName.replace("[]", "");
            }
            if ($("#" + elementID + "_delete").length == 0) {
                $("#" + elementID).after(
                    '<input type="hidden" name="' +
                    elementName +
                    '_delete[' + elementIndex + ']" id="' +
                    elementID +
                    '_delete" value="yes">'
                );
            }
        });

        const hsn_status = "{{ $invoiceSetting->hsn_sac_code_show }}";
        const defaultClient = "{{ request('default_client') }}";

        quillMention(null, '#description');

        $('.custom-date-picker').each(function(ind, el) {
            datepicker(el, {
                position: 'bl',
                ...datepickerConfig
            });
        });

        const dp1 = datepicker('#valid_till', {
            position: 'bl',
            dateSelected: new Date("{{ str_replace('-', '/', today()->addDays(30)) }}"),
            ...datepickerConfig
        });

        const resetAddProductButton = () => {
            $("#add-products").val('').selectpicker("refresh");
        };

        $('#add-products').on('changed.bs.select', function(e, clickedIndex, isSelected, previousValue) {
            e.stopImmediatePropagation()
            var id = $(this).val();
            if (previousValue != id && id != '') {
                addProduct(id);
                resetAddProductButton();
            }
        });

        function addProduct(id) {
            var currencyId = $('#currency_id').val();

            $.easyAjax({
                url: "{{ route('estimates.add_item') }}",
                type: "GET",
                data: {
                    id: id,
                    currencyId: currencyId
                },
                blockUI: true,
                success: function(response) {
                    if ($('input[name="item_name[]"]').val() == '') {
                        $("#sortable .item-row").remove();
                    }
                    $(response.view).hide().appendTo("#sortable").fadeIn(500);
                    calculateTotal();

                    var noOfRows = $(document).find('#sortable .item-row').length;
                    var i = $(document).find('.item_name').length - 1;
                    var itemRow = $(document).find('#sortable .item-row:nth-child(' + noOfRows +
                        ') select.type');
                    itemRow.attr('id', 'multiselect' + i);
                    itemRow.attr('name', 'taxes[' + i + '][]');
                    $(document).find('#multiselect' + i).selectpicker();

                    $(document).find('#dropify' + i).dropify({
                        messages: dropifyMessages
                    });
                }
            });
        }

        $(document).on('click', '#add-item', function() {

            var i = $(document).find('.item_name').length;
            var item = ' <div class="d-flex px-4 py-3 c-inv-desc item-row">' +
                '<div class="c-inv-desc-table w-100 d-lg-flex d-md-flex d-block">' +
                '<table width="100%">' +
                '<tbody>' +
                '<tr class="text-dark-grey font-weight-bold f-14">' +
                '<td width="{{ $invoiceSetting->hsn_sac_code_show ? '40%' : '50%' }}" class="border-0 inv-desc-mbl btlr">@lang('app.description')</td>';

            if (hsn_status == 1) {
                item += '<td width="10%" class="border-0" align="right">@lang('app.hsnSac')</td>';
            }

            item +=
                `<td width="10%" class="border-0" align="right">@lang("modules.invoices.qty")</td>
                <td width="10%" class="border-0" align="right">@lang('modules.invoices.unitPrice')</td>
                <td width="13%" class="border-0" align="right">@lang('modules.invoices.tax')</td>
                <td width="17%" class="border-0 bblr-mbl" align="right">@lang('modules.invoices.amount')</td>
                </tr>` +
                '<tr>' +
                '<td class="border-bottom-0 btrr-mbl btlr">' +
                `<input type="text" class="f-14 border-0 w-100 item_name form-control" name="item_name[]" placeholder="@lang('modules.expenses.itemName')">` +
                '</td>' +
                '<td class="border-bottom-0 d-block d-lg-none d-md-none">' +
                `<textarea class="f-14 border-0 w-100 mobile-description form-control" name="item_summary[]" placeholder="@lang('placeholders.invoices.description')"></textarea>` +
                '</td>';

            if (hsn_status == 1) {
                item += '<td class="border-bottom-0">' +
                    '<input type="text" min="1" class="f-14 border-0 w-100 text-right hsn_sac_code form-control" value="" name="hsn_sac_code[]">' +
                    '</td>';
            }

            item += '<td class="border-bottom-0">' +
                '<input type="number" min="1" class="form-control f-14 border-0 w-100 text-right quantity mt-3" value="1" name="quantity[]">' +
                `<select class="text-dark-grey float-right border-0 f-12" name="unit_id[]">
                    @foreach ($units as $unit)
                        <option
                        @if ($unit->default == 1) selected @endif
                        value="{{ $unit->id }}">{{ $unit->unit_type }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="product_id[]" value="">`+
                '</td>' +
                '<td class="border-bottom-0">' +
                '<input type="number" min="1" class="f-14 border-0 w-100 text-right cost_per_item" placeholder="0.00" value="0" name="cost_per_item[]">' +
                '</td>' +
                '<td class="border-bottom-0">' +
                '<div class="select-others height-35 rounded border-0">' +
                '<select id="multiselect' + i + '" name="taxes[' + i +
                '][]" multiple="multiple" class="select-picker type customSequence" data-size="3">'
            @foreach ($taxes as $tax)
                +'<option data-rate="{{ $tax->rate_percent }}" data-tax-text="{{ $tax->tax_name .':'. $tax->rate_percent }}%" value="{{ $tax->id }}">' +
                '{{ $tax->tax_name }}:{{ $tax->rate_percent }}%</option>'
            @endforeach +
            '</select>' +
            '</div>' +
            '</td>' +
            '<td rowspan="2" align="right" valign="top" class="bg-amt-grey btrr-bbrr">' +
            '<span class="amount-html">0.00</span>' +
            '<input type="hidden" class="amount" name="amount[]" value="0">' +
            '</td>' +
            '</tr>' +
            '<tr class="d-none d-md-table-row d-lg-table-row">' +
            '<td colspan="{{ $invoiceSetting->hsn_sac_code_show ? 4 : 3 }}" class="dash-border-top bblr">' +
            '<textarea class="f-14 border-0 w-100 desktop-description form-control" name="item_summary[]" placeholder="@lang('placeholders.invoices.description')"></textarea>' +
            '</td>' +
            '<td class="border-left-0">' +
            '<input type="file" class="dropify" id="dropify' + i +
                '" name="invoice_item_image[]" data-allowed-file-extensions="png jpg jpeg bmp" data-messages-default="test" data-height="70" /><input type="hidden" name="invoice_item_image_url[]">' +
                '</td>' +
                '</tr>' +
                '</tbody>' +
                '</table>' +
                '</div>' +
                '<a href="javascript:;" class="d-flex align-items-center justify-content-center ml-3 remove-item"><i class="fa fa-times-circle f-20 text-lightest"></i></a>' +
                '</div>';
            $(item).hide().appendTo("#sortable").fadeIn(500);
            $('#multiselect' + i).selectpicker();

            $('#dropify' + i).dropify({
                messages: dropifyMessages
            });
        });

        $('#saveInvoiceForm').on('click', '.remove-item', function() {
            $(this).closest('.item-row').fadeOut(300, function() {
                $(this).remove();
                $('select.customSequence').each(function(index) {
                    $(this).attr('name', 'taxes[' + index + '][]');
                    $(this).attr('id', 'multiselect' + index + '');
                });
                calculateTotal();
            });
        });

        $('.save-form').click(function() {
            let note = document.getElementById('description').children[0].innerHTML;
            document.getElementById('description-text').value = note;

            var type = $(this).data('type');

            if (KTUtil.isMobileDevice()) {
                $('.desktop-description').remove();
            } else {
                $('.mobile-description').remove();
            }

            calculateTotal();

            var discount = $('#discount_amount').html();
            var total = $('.sub-total-field').val();

            if (parseFloat(discount) > parseFloat(total)) {
                Swal.fire({
                    icon: 'error',
                    text: "{{ __('messages.discountExceed') }}",

                    customClass: {
                        confirmButton: 'btn btn-primary',
                    },
                    showClass: {
                        popup: 'swal2-noanimation',
                        backdrop: 'swal2-noanimation'
                    },
                    buttonsStyling: false
                });

                return false;
            }

            $.easyAjax({
                url: "{{ route('estimates.store') }}?type=" + type,
                container: '#saveInvoiceForm',
                type: "POST",
                blockUI: true,
                file: true,
                data: $('#saveInvoiceForm').serialize(),
                success: function(response) {
                    if (response.status == 'success') {
                        window.location.href = response.redirectUrl;
                    }
                }
            })
        });

        $('#saveInvoiceForm').on('click', '.remove-item', function() {
            $(this).closest('.item-row').fadeOut(300, function() {
                $(this).remove();
                $('select.customSequence').each(function(index) {
                    $(this).attr('name', 'taxes[' + index + '][]');
                    $(this).attr('id', 'multiselect' + index + '');
                });
                calculateTotal();
            });
        });

        $('#saveInvoiceForm').on('keyup', '.quantity,.cost_per_item,.item_name, .discount_value', function() {
            var quantity = $(this).closest('.item-row').find('.quantity').val();
            var perItemCost = $(this).closest('.item-row').find('.cost_per_item').val();
            var amount = (quantity * perItemCost);

            $(this).closest('.item-row').find('.amount').val(decimalupto2(amount));
            $(this).closest('.item-row').find('.amount-html').html(decimalupto2(amount));

            calculateTotal();
        });

        $('#saveInvoiceForm').on('change', '.type, #discount_type, #calculate_tax', function() {
            var quantity = $(this).closest('.item-row').find('.quantity').val();
            var perItemCost = $(this).closest('.item-row').find('.cost_per_item').val();
            var amount = (quantity * perItemCost);

            $(this).closest('.item-row').find('.amount').val(decimalupto2(amount));
            $(this).closest('.item-row').find('.amount-html').html(decimalupto2(amount));

            calculateTotal();
        });

        $('#saveInvoiceForm').on('input', '.quantity', function() {
            var quantity = $(this).closest('.item-row').find('.quantity').val();
            var perItemCost = $(this).closest('.item-row').find('.cost_per_item').val();
            var amount = (quantity * perItemCost);

            $(this).closest('.item-row').find('.amount').val(decimalupto2(amount));
            $(this).closest('.item-row').find('.amount-html').html(decimalupto2(amount));

            calculateTotal();
        });

        calculateTotal();

        init(RIGHT_MODAL);
    });

    function ucWord(str) {
        str = str.toLowerCase().replace(/\b[a-z]/g, function(letter) {
            return letter.toUpperCase();
        });
        return str;
    }

</script>
@endif
