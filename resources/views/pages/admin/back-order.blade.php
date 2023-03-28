@extends('layouts.app')

@section('header_scripts')
    <script>
        const search_url = '{{ route('search_back_order') }}';
    </script>
    <script src="{{ asset('js/scope/search.js') }}" defer></script>
    <script src="{{ asset('js/scope/back_order.js') }}" defer type="module"></script>
@endsection

@section('admin_content')
    <div class="row px-xl-5">
        @include('layouts.heading')
        <div class="col-xl-12">
            <div class="row">
                <div class="col-12 col-xl-5">
                    <a href="{{ route('orders') }}" class="btn btn-success py-xl-2 px-xl-3 mb-xl-3 text-primary"><i
                            class="fa fa-list"></i> View Orders</a>
                    <a href="{{ url('/inventory/purchase-order') }}"
                        class="btn btn-success py-xl-2 px-xl-3 mb-xl-3 text-primary">
                        <i class="fa fa-plus-circle"></i> Purchase Order</a>
                </div>
                
                <div class="col-xl-5 ms-auto">
                    <form id="filters" class="d-flex align-middle" action="{{ route('back_order') }}"
                        method="GET">
                        @csrf
                        <label for="from" class="my-auto me-1">From</label>
                        <input class="form-control my-auto me-xl-3" type="text" id="from" name="from" autocomplete="off"
                            value="{{ request()->input('from') }}">
                        <label for="to" class="my-auto me-1">To</label>
                        <input class="form-control my-auto" type="text" id="to" name="to" autocomplete="off"
                            value="{{ request()->input('to') }}">
                        <input type="submit" class="btn btn-primary px-4 my-auto" value="Filter">
                    </form>
                    {{-- <a href="{{ route('print_inventory_order_report', [
                        'from' => request()->input('from'),
                        'to' => request()->input('to'),
                    ]) }}"
                        class="btn btn-success text-white mt-3 float-end">
                        <i class="fa-solid fa-print"></i> Print</a> --}}
                </div>
            </div>
        </div>
        <div class="col-xl-12">
            <div class="w-25 my-3">
                <input type="text" name="q" id="q" form="filters" class="form-control" placeholder="Search" value="{{ $q }}">
            </div>
        </div>
        <div class="col-xl-12">
            <table id="product_list" class="table table-striped">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Item Code</th>
                        <th scope="col">Product Name</th>
                        <th scope="col">Base Price</th>
                        <th scope="col">Markup</th>
                        <th scope="col">Selling Price</th>
                        <th scope="col">Expiration Date</th>
                        <th scope="col">Date Received</th>
                        <th scope="col">Back Order</th>
                    </tr>
                </thead>
                <tbody>
                    @include('components.admin.back-order-list')
                </tbody>
            </table>
            <div id="pages">
                {{ $products->links() }}
            </div>
            @empty($products)
                @include('layouts.empty-table')
            @endempty
        </div>
    </div>
@endsection

@section('content')
    @include('components.admin.content')
@endsection
