<!-- Datatables -->
<script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}" defer="defer"></script>
<script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}" defer="defer"></script>
{{-- <script src="{{ asset('vendor/datatables/dataTables.responsive.min.js') }}"></script> --}}
{{-- <script src="{{ asset('vendor/datatables/responsive.bootstrap.min.js') }}"></script> --}}
<script src="{{ asset('vendor/datatables/dataTables.buttons.min.js') }}" defer="defer"></script>
<script src="{{ asset('vendor/datatables/buttons.bootstrap4.min.js') }}" defer="defer"></script>
<script src="{{ asset('vendor/datatables/buttons.server-side.js') }}" defer="defer"></script>
{!! $dataTable->scripts() !!}

<script>
    if (!KTUtil.isMobileDevice()) {
        $('.table-responsive').on('show.bs.dropdown', function () {
            $('.table-responsive').css( "overflow", "inherit" );
        });

        $('.table-responsive').on('hide.bs.dropdown', function () {
            $('.table-responsive').css( "overflow", "auto" );
        })
    }
</script>

@include('sections.daterange_js')
