<div class="col-lg-12 col-md-12 ntfcn-tab-content-left w-100 p-4 ">
    @method('POST')
    <div class="row">
        @if (in_array('invoices', user_modules()))
            <div class="col-lg-3">
                <x-forms.text class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('modules.invoiceSettings.invoicePrefix')" :fieldPlaceholder="__('placeholders.invoices.invoicePrefix')" fieldName="invoice_prefix"
                    fieldId="invoice_prefix" :fieldValue="$invoiceSetting->invoice_prefix" fieldRequired="true" />
            </div>

            <div class="col-lg-3">
                <x-forms.text class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('modules.invoiceSettings.invoiceNumberSeparator')" :fieldPlaceholder="__('placeholders.invoices.invoiceNumberSeparator')"
                    fieldName="invoice_number_separator" fieldId="invoice_number_separator" :fieldValue="$invoiceSetting->invoice_number_separator" />
            </div>

            <div class="col-lg-3">
                <x-forms.number class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('modules.invoiceSettings.invoiceDigit')" fieldName="invoice_digit"
                    fieldId="invoice_digit" :fieldValue="$invoiceSetting->invoice_digit" minValue="2" />
            </div>

            <div class="col-lg-3">
                <x-forms.text class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('modules.invoiceSettings.invoiceLookLike')" fieldId="invoice_look_like"
                    fieldName="invoice_look_like" fieldReadOnly="true" />
            </div>
  
            <div class="col-lg-3">
                <x-forms.text class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('modules.invoiceSettings.credit_notePrefix')" :fieldPlaceholder="__('placeholders.invoices.creditNotePrefix')"
                    fieldName="credit_note_prefix" fieldRequired="true" fieldId="credit_note_prefix" :fieldValue="$invoiceSetting->credit_note_prefix" />
            </div>

            <div class="col-lg-3">
                <x-forms.text class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('modules.invoiceSettings.credit_noteNumberSeparator')" :fieldPlaceholder="__('placeholders.invoices.credit_noteNumberSeparator')"
                    fieldName="credit_note_number_separator" fieldId="credit_note_number_separator" :fieldValue="$invoiceSetting->credit_note_number_separator" />
            </div>

            <div class="col-lg-3">
                <x-forms.number class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('modules.invoiceSettings.credit_noteDigit')" fieldName="credit_note_digit"
                    fieldId="credit_note_digit" :fieldValue="$invoiceSetting->credit_note_digit" minValue="2" />
            </div>

            <div class="col-lg-3">
                <x-forms.text class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('modules.invoiceSettings.credit_noteLookLike')" fieldName="credit_note_look_like"
                    fieldId="credit_note_look_like" fieldValue="" fieldReadOnly="true" />
            </div>
        @endif

        @if (in_array('estimates', user_modules()))
            <div class="col-lg-3">
                <x-forms.text class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('modules.invoiceSettings.estimatePrefix')" :fieldPlaceholder="__('placeholders.invoices.estimatePrefix')" fieldName="estimate_prefix"
                    fieldRequired="true" fieldId="estimate_prefix" :fieldValue="$invoiceSetting->estimate_prefix" />
            </div>

            <div class="col-lg-3">
                <x-forms.text class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('modules.invoiceSettings.estimateNumberSeparator')" :fieldPlaceholder="__('placeholders.invoices.estimateNumberSeparator')"
                    fieldName="estimate_number_separator" fieldId="estimate_number_separator" :fieldValue="$invoiceSetting->estimate_number_separator" />
            </div>

            <div class="col-lg-3">
                <x-forms.number class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('modules.invoiceSettings.estimateDigit')" fieldName="estimate_digit"
                    fieldId="estimate_digit" :fieldValue="$invoiceSetting->estimate_digit" minValue="2" />
            </div>

            <div class="col-lg-3">
                <x-forms.text class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('modules.invoiceSettings.estimateLookLike')" fieldName="estimate_look_like"
                    fieldId="estimate_look_like" fieldValue="" fieldReadOnly="true" />
            </div>
        @endif

        @if (in_array('orders', user_modules()))
            <div class="col-lg-3">
                <x-forms.text class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('modules.invoiceSettings.orderPrefix')" :fieldPlaceholder="__('modules.invoiceSettings.orderPrefix')" fieldName="order_prefix"
                    fieldRequired="true" fieldId="order_prefix" :fieldValue="$invoiceSetting->order_prefix" />
            </div>

            <div class="col-lg-3">
                <x-forms.text class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('modules.invoiceSettings.orderNumberSeparator')" :fieldPlaceholder="__('modules.invoiceSettings.orderNumberSeparator')"
                    fieldName="order_number_separator" fieldId="order_number_separator" :fieldValue="$invoiceSetting->order_number_separator" />
            </div>

            <div class="col-lg-3">
                <x-forms.number class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('modules.invoiceSettings.orderDigit')" fieldName="order_digit"
                    fieldId="order_digit" :fieldValue="$invoiceSetting->order_digit" minValue="2" />
            </div>

            <div class="col-lg-3">
                <x-forms.text class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('modules.invoiceSettings.orderLookLike')" fieldName="order_look_like"
                    fieldId="order_look_like" fieldValue="" fieldReadOnly="true" />
            </div>
        @endif
    </div>
</div>

<!-- Buttons Start -->
<div class="w-100 border-top-grey">
    <x-setting-form-actions>
        <x-forms.button-primary id="save-prefix-form" class="mr-3" icon="check">@lang('app.save')
        </x-forms.button-primary>
    </x-setting-form-actions>
</div>
<!-- Buttons End -->

<script>
    // save prefix setting
    $('#save-prefix-form').click(function() {
        $.easyAjax({
            url: "{{ route('invoice_settings.update_prefix', $invoiceSetting->id) }}",
            container: '#editSettings',
            type: "POST",
            redirect: true,
            file: true,
            data: $('#editSettings').serialize(),
            disableButton: true,
            blockUI: true,
            buttonSelector: "#save-prefix-form",
        })
    });

    $('#invoice_prefix, #invoice_number_separator, #invoice_digit, #estimate_prefix,#estimate_number_separator, #estimate_digit, #credit_note_prefix, #credit_note_number_separator, #credit_note_digit, #order_prefix, #order_number_separator, #order_digit').on('keyup', function() {
        genrateInvoiceNumber();
    });

    genrateInvoiceNumber();

    function genrateInvoiceNumber() {
        var invoicePrefix = $('#invoice_prefix').val();
        var invoiceNumberSeparator = $('#invoice_number_separator').val();
        var invoiceDigit = $('#invoice_digit').val();
        var invoiceZero = '';
        for ($i = 0; $i < invoiceDigit - 1; $i++) {
            invoiceZero = invoiceZero + '0';
        }
        invoiceZero = invoiceZero + '1';
        var invoice_no = invoicePrefix + invoiceNumberSeparator + invoiceZero;
        $('#invoice_look_like').val(invoice_no);

        var orderPrefix = $('#order_prefix').val();
        var orderNumberSeparator = $('#order_number_separator').val();
        var orderDigit = $('#order_digit').val();
        var orderZero = '';
        for ($i = 0; $i < orderDigit - 1; $i++) {
            orderZero = orderZero + '0';
        }
        orderZero = orderZero + '1';
        var order_no = orderPrefix + orderNumberSeparator + orderZero;
        $('#order_look_like').val(order_no);

        var estimatePrefix = $('#estimate_prefix').val();
        var estimateNumberSeparator = $('#estimate_number_separator').val();
        var estimateDigit = $('#estimate_digit').val();
        var estimateZero = '';
        for ($i = 0; $i < estimateDigit - 1; $i++) {
            estimateZero = estimateZero + '0';
        }
        estimateZero = estimateZero + '1';
        var estimate_no = estimatePrefix + estimateNumberSeparator + estimateZero;
        $('#estimate_look_like').val(estimate_no);

        var creditNotePrefix = $('#credit_note_prefix').val();
        var creditNoteNumberSeparator = $('#credit_note_number_separator').val();
        var creditNoteDigit = $('#credit_note_digit').val();
        var creditNoteZero = '';
        for ($i = 0; $i < creditNoteDigit - 1; $i++) {
            creditNoteZero = creditNoteZero + '0';
        }
        creditNoteZero = creditNoteZero + '1';
        var creditNote_no = creditNotePrefix + creditNoteNumberSeparator + creditNoteZero;
        $('#credit_note_look_like').val(creditNote_no);

    }
</script>
