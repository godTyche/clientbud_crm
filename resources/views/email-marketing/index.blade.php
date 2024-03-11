@extends('layouts.app')

@push('datatable-styles')
    @include('sections.datatable_css')
@endpush

@section('filter-section')

@endsection

@php
$addProductPermission = user()->permission('add_product');
$addOrderPermission = user()->permission('add_order');
@endphp

@section('content')
    <!-- CONTENT WRAPPER START -->
 
    <!-- CONTENT WRAPPER END -->
    @if(in_array('admin', user()->roles->pluck('name')->toArray()))
        <iframe src="https://em.clientbud.com/admin/dashboard" style="width: 100%; height: 100vh;" frameborder="0"></iframe>
    @else
        <iframe src="https://em.clientbud.com/user/dashboard" style="width: 100%; height: 100vh;" frameborder="0"></iframe>
    @endif

@endsection

@push('scripts')
    <script>
    </script>
@endpush
