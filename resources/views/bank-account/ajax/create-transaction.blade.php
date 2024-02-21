<div class="row">
    <div class="col-sm-12">
        <x-form id="save-bank-transaction-data-form">
            <div class="add-client bg-white rounded">
                <h4 class="mb-0 p-20 f-21 font-weight-normal text-capitalize border-bottom-grey">
                    @if($type == 'account')
                        @lang('modules.bankaccount.bankTransfer')
                    @elseif ($type == 'deposit')
                        @lang('modules.bankaccount.deposit')
                    @else
                        @lang('modules.bankaccount.withdraw')
                    @endif
                    @lang('app.details')</h4>
                <div class="row p-20">
                    <div class="col-lg-12">
                        <div class="row">
                            <input type="hidden" value="{{$type}}" name="type">

                            @if($type == 'deposit')
                                <div class="col-md-4">
                                    <input type="hidden" name="to_bank_account" value="{{ $currentAccount->id }}">
                                    <x-forms.text fieldReadOnly="true" fieldId="to_bank_account" :fieldLabel="__('modules.bankaccount.toBankAccount')"
                                    fieldName="to_bank_account1"
                                    :fieldValue="(($currentAccount->type == 'bank') ? $currentAccount->bank_name .' | '.$currentAccount->account_name : $currentAccount->account_name)" />
                                </div>
                            @endif

                            @if($type == 'withdraw' || $type == 'account')
                                <div class="col-md-4">
                                    <input type="hidden" name="from_bank_account" value="{{ $currentAccount->id }}">
                                    <x-forms.text fieldReadOnly="true" fieldId="to_bank_account" :fieldLabel="__('modules.bankaccount.fromBankAccount')"
                                    fieldName="from_bank_account1"
                                    :fieldValue="(($currentAccount->type == 'bank') ? $currentAccount->bank_name .' | '.$currentAccount->account_name : $currentAccount->account_name)" />
                                </div>
                            @endif

                            @if($type == 'account')

                                <div class="col-md-4">
                                    <x-forms.select fieldId="to_bank_account" :fieldLabel="__('modules.bankaccount.toBankAccount')" fieldName="to_bank_account"
                                        search="true">
                                        <option value="">--</option>
                                        @foreach ($bankAccounts as $bankAccount)
                                            <option value="{{ $bankAccount->id }}">@if($bankAccount->type == 'bank')
                                                {{ $bankAccount->bank_name }} | @endif {{ $bankAccount->account_name }}
                                            </option>
                                        @endforeach
                                    </x-forms.select>
                                </div>
                            @endif

                            <div class="col-md-4">
                                <x-forms.number fieldId="amount" :fieldLabel="__('app.amount')" fieldName="amount" :fieldHelp="__('modules.bankaccount.currencyHelp').$currentAccount->currency->currency_code"
                                    :fieldPlaceholder="__('placeholders.price')" fieldRequired="true"></x-forms.number>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group my-3">
                                    <x-forms.textarea class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('modules.bankaccount.memo')" fieldName="memo"
                                        fieldId="memo">
                                    </x-forms.textarea>
                                </div>
                            </div>


                        </div>
                    </div>

                </div>

                <x-form-actions>
                    <x-forms.button-primary id="save-transaction" class="mr-3" icon="check">@lang('app.save')
                    </x-forms.button-primary>
                    <x-forms.button-cancel :link="route('bankaccounts.show', $accountId)" class="border-0">@lang('app.cancel')
                    </x-forms.button-cancel>
                </x-form-actions>
            </div>
        </x-form>

    </div>
</div>

<script>
    $(document).ready(function() {

        $('#save-transaction').click(function() {
            const url = "{{ route('bankaccounts.store_transaction') }}";

            $.easyAjax({
                url: url,
                container: '#save-bank-transaction-data-form',
                type: "POST",
                disableButton: true,
                blockUI: true,
                file: true,
                buttonSelector: "#save-transaction",
                data: $('#save-bank-transaction-data-form').serialize(),
                success: function(response) {
                    if (response.status == 'success') {
                        window.location.href = response.redirectUrl;
                    }
                }
            });
        });

        init(RIGHT_MODAL);
    })
</script>
