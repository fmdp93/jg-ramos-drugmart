@extends('layouts.inventory')

@section('inventory_modal')
    @include('components.inventory-modal')
@endsection

@section('layout_header_scripts')
    <script defer type="module">
        import {objInventory} from "/js/scope/inventory.js"
        const archive_action = {
            archive_action: '{{ $archive_action }}'
        }
        objInventory.ObjectSearch.appendParam(archive_action);
    </script>
@endsection

@section('inventory_headings')
    <div class="col-12">
        <a id="btn-deliveries" href="{{ url('/inventory/orders') }}" class="btn btn-success mb-xl-3 text-primary"
            title="[Alt] + [V]">
            <i class="fa fa-list"></i> View Orders
        </a>
        <a href="{{ url('/inventory/purchase-order') }}" class="btn btn-success mb-xl-3 text-primary">
            <i class="fa fa-plus-circle"></i> Purchase Order
        </a>
        <a href="{{ route('inventory_archives') }}" class="btn btn-success mb-xl-3 text-primary">
            <i class="fa fa-box-archive"></i> Archives</a>
            <a href="{{ url('/products') }}" class="btn btn-success mb-xl-3 text-primary">
            <i class="fa fa-plus-circle"></i> Add/Update Products
        </a> 
    </div>
    <div class="row align-items-center mb-xl-4">
        <div class="col-xl-3">
            @include('components.search')
        </div>
        <div class="col-xl-auto ps-5">
            <input type="hidden" name="stock_filter" value="{{ request()->input('stock_filter') }}">            
            <button id="normal-stock" data-filter="normal" class="btn btn-button-submit px-2 py-2">
                <i class="fa-solid fa-circle-check"></i>
                Normal Stock
            </button>
            <button id="half-stock" data-filter="half" class="btn btn-half px-2 py-2">
                <i class="fa-solid fa-arrow-trend-down"></i>
                Half Stock
            </button>
            
        </div>
        <div class="col-xl-auto ms-auto">
            <div class="d-flex align-items-center">
                <label for="expiry" class="pe-1">Expiry</label>
                <select name="expiry" form="{{ $form_id ?? '' }}" id="expiry" class="form-select">
                    <option value="latest" {{ $expiry == 'latest' ? 'selected' : '' }}>Latest</option>
                    <option value="oldest" {{ $expiry == 'oldest' ? 'selected' : '' }}>Oldest</option>
                </select>
            </div>
        </div>
        <div class="col-xl-auto">
            <div class="d-flex align-items-center">
                <label for="category_id" class="pe-1">Category</label>
                <select name="category_id" id="category_id" class="form-select" form="{{ $form_id ?? '' }}">
                    <option value="0">All</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" {{ $category_id == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
@endsection

