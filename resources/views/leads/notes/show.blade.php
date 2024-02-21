<div class="row">
    <div class="col-sm-12">
        <x-cards.data :title="__('app.note').' '.__('app.details')" class=" mt-4">
            <x-cards.data-row :label="__('modules.client.noteTitle')"
                :value="$note->title" />

            <x-cards.data-row :label="__('modules.client.noteDetail')" :value="$note->details" html="true" />

        </x-cards.data>
    </div>
</div>
