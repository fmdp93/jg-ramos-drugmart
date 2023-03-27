@php
use App\Http\Controllers\ProductsController;
@endphp
@extends('layouts.app')

@section('title')
    Products
@endsection

@section('header_scripts')
    <script>
        const product_search_url = '{{ ProductsController::$product_search_url }}';
    </script>
    <script src="{{ asset('js/scope/search.js') }}" defer></script>
    <script src="{{ asset('js/scope/products.js') }}" defer type="module"></script>
@endsection

@section('admin_content')
    <div class="row px-xl-5">
        @error('product_id')
            <div class="row">
                <div class="col-xl-4">
                    @include('components.error-message')
                </div>
            </div>
        @enderror
        @include('layouts.heading')
        <div class="col-xl-3">
            <div class="row">
                <div class="col-12">
                    <a href="{{ action([ProductsController::class, 'addProduct']) }}"
                        class="btn btn-success mb-xl-3 text-primary"><i class="fa fa-plus-circle"></i> Add Item</a>
                        <a href="{{ route('add_supplier') }}"
                class="btn btn-success mb-xl-3 text-primary"><i class="fa fa-plus-circle"></i> Add Supplier</a>
                </div>
            </div>
            <form id="{{ $form_id }}" action="{{ url('/product/update') }}" method="POST">
                @csrf
                {{-- <input type="hidden" name="search"> --}}
                <input type="hidden" name="page" value="{{ $products->currentPage() }}">
                <input type="hidden" name="product_id" id="product_id" value="{{ old('product_id') }}">
                <label for="item_code">Item Code</label>
                <input name="item_code" id="item_code" class="form-control form-control-xl mb-xl-3" type="text"
                    aria-label="item_code" value="{{ old('item_code') }}" readonly>
                <label for="item_name">Item Name</label>
                <input name="item_name" id="item_name" class="form-control form-control-xl mb-xl-3" type="text"
                    aria-label="name" value="{{ old('item_name') }}" readonly>

                @error('base_price')
                    @include('components.error-message')
                @enderror

                @error('markup')
                    @include('components.error-message')
                @enderror

                @error('selling_price')
                    @include('components.error-message')
                @enderror

                <div class="row">
                    <div class="col-xl-6">
                        <label for="base_price">Base Price</label>
                        <input name="base_price" id="base_price" class="form-control form-control-xl mb-xl-3" type="number"
                            min="0" step=".01" aria-label="base_price" value="{{ old('base_price') }}"
                            {{ getInputFloatPattern() }}>
                    </div>
                    <div class="col-xl-6">
                        <label for="markup">Markup Price</label>
                        <div class="input-group mb-xl-3">
                            <input name="markup" id="markup" class="form-control form-control-xl" type="number" min="0"
                                step=".01" aria-label="markup" aria-describedby="markup-addon"
                                value="{{ old('markup') }}" {{ getInputFloatPattern() }}>
                            <span class="input-group-text" id="markup-addon">%</span>
                        </div>
                    </div>
                    <div class="col-xl-12">
                        <label for="selling_price">Selling Price</label>
                        <input name="selling_price" id="selling_price" class="form-control form-control-xl mb-xl-3"
                            type="number" step=".01" aria-label="selling_price" value="{{ old('selling_price') }}">
                    </div>
                </div>
                <label for="name">Expiration</label>
                <input name="expiration_date" id="expiration_date" class="form-control form-control-xl mb-xl-3" type="text"
                    placeholder="2030-12-30" aria-label="expiration_date" autocomplete="off"
                    value="{{ old('expiration_date') }}">
                @error('supplier_search_id')
                    @include('components.error-message')
                @enderror
                <br>
                <label for="supplier_search">Search Supplier</label>
                <input type="text" name="supplier_search" id="supplier_search" value="{{ old('supplier_search') }}"
                    class="form-control mb-3" autocomplete="off">
                <input type="hidden" name="supplier_search_id" id="supplier_search_id"
                    value="{{ old('supplier_search_id') }}">

                <label for="vendor">Vendor</label>
                <input type="text" name="vendor" id="vendor" value="{{ old('vendor') }}" class="form-control mb-3"
                    autocomplete="off" readonly>

                <label for="company">Company</label>
                <input type="text" name="company" id="company" value="{{ old('company') }}" class="form-control mb-3"
                    autocomplete="off" readonly>

                <label for="contact">Contact Details</label>
                <input type="text" name="contact" id="contact" value="{{ old('contact') }}" class="form-control mb-3"
                    autocomplete="off" readonly>

                <label for="address">Address</label>
                <input type="text" name="address" id="address" value="{{ old('address') }}" class="form-control mb-3"
                    autocomplete="off" readonly>

                <input type="submit" value="Update Item" class="btn btn-primary">
            </form>
        </div>
        <div class="col-xl-9">
            <div class="row mb-xl-4">
                <div class="col-xl-3">
                    <label for="search">Search Product</label>
                    @include('components.search')
                    <input type="hidden" name="action" id="action" value="{{ $action }}">
                    <input type="hidden" name="action" id="action_print_barcode" value="{{ $action_print_barcode }}">
                </div>
                
                <div class="col-xl-3">
                    <label for="category_id">Filter By Category</label>
                    <select name="category_id" id="category_id" class="form-select" form="{{ $form_id }}">
                        <option value="0">All</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ $category_id == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <table id="products_list" class="table table-striped">
                <thead>
                    <tr>
                        <th scope="col">Code</th>
                        <th scope="col">Name</th>
                        <th scope="col">Description</th>
                        <th scope="col">Category</th>
                        <th scope="col">Base Price</th>
                        <th scope="col">Markup</th>
                        <th scope="col">Selling Price</th>
                        <th scope="col">Max Stock</th>
                        <th scope="col">Unit</th>
                        <th scope="col">Expiration</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @include('components.products-list')
                </tbody>
            </table>
            <div id="pages">
                {{ $products->links() }}
            </div>
            @include('layouts.empty-table')
        </div>
    </div>
@endsection

@section('content')
    @include('components.admin.content')
@endsection
