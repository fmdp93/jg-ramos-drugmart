@php
use App\Http\Controllers\AccountsController;
@endphp
@extends('layouts.app')

@section('header_scripts')
    <script>
        const search_url = '{{ route('search_supplier') }}';
        const delete_action = '{{ $delete_action }}';
        const edit_action = '{{ $edit_action }}';
    </script>
    <script src="{{ asset('js/scope/search.js') }}" defer></script>
    <script src="{{ asset('js/scope/supplier.js') }}" defer type="module"></script>
@endsection

@section('admin_content')
    <div class="row px-xl-5">
        @include('layouts.heading')
        <div class="col-xl-12">
            <div class="row">
                <div class="col-xl-2">
                    <a href="{{ route('add_supplier') }}" class="btn btn-success py-xl-2 px-xl-3 mb-xl-3 text-primary"><i
                            class="fa fa-plus-circle"></i> Add Supplier</a>
                    </div>
                    <div class="col-xl-2">
                    <a href="{{ url('/inventory/purchase-order') }}"
                        class="btn btn-success py-xl-2 px-xl-3 mb-xl-3 text-primary">
                        <i class="fa fa-plus-circle"></i> Purchase Order</a>
                </div>
                <div class="col-xl-3 ms-auto">
                    @include('components.search')
                </div>
            </div>
        </div>
        <div class="col-xl-12">
            <table id="supplier_list" class="table table-striped">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Vendor</th>
                        <th scope="col">Company Name</th>
                        <th scope="col">Contact</th>
                        <th scope="col">Address</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @include('components.admin.supplier-list')
                </tbody>
            </table>
            <div id="pages">
                {{ $suppliers->links() }}
            </div>
            @empty($suppliers)
                @include('layouts.empty-table')
            @endempty
        </div>
    </div>
@endsection

@section('content')
    @include('components.admin.content')
@endsection
