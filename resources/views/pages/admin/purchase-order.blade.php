@php
use App\Http\Controllers\InventoryController;
@endphp

@extends('layouts.app')

@section('title')
    Purchase Order
@endsection

@section('header_scripts')
    <script src="{{ asset('js/scope/purchase-order.js') }}" defer type="module"></script>
@endsection

@section('admin_content')
    <div class="row px-xl-5 mb-xl-3">
        @include('layouts.heading')
        <div class="col-xl-12">
            <a id="btn-deliveries" href="{{ url('/inventory/orders') }}" class="btn btn-success mb-xl-3 text-primary"
                title="[Alt] + [V]">
                <i class="fa fa-list"></i> View Orders
            </a>
            <a href="{{ route('suppliers') }}" class="btn btn-success py-xl-2 px-xl-3 mb-xl-3 text-primary"><i
                    class="fa-solid fa-building"></i> Suppliers</a>
        </div>
        <div class="col-xl-3">
            <form id="{{ $form = 'purchase-order' }}" action="{{ action([InventoryController::class, 'store']) }}"
                method="POST" class="d-flex flex-column pb-5">
                @csrf
                <label for="supplier">Supplier</label>
                <select name="supplier" id="supplier" class="form-select mb-3">
                    <option value="">---Choose Supplier---</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" 
                            {{ old('supplier') == $supplier->id ? 'selected' : '' }}>
                            {{ sprintf("%s, %s", ucwords($supplier->vendor), ucwords($supplier->company_name)) }}
                        </option>
                    @endforeach
                </select>             
                <button id="add-supplier-items" class="float-end btn btn-button-submit text-white py-xl-2 px-xl-5" type="submit">Add
                    Supplier's Items</button>
            </form>            
            <form action="#" class="d-flex flex-column">
                <label for="item_code">Item Code</label>
                <input name="item_code" id="item_code" class="form-control form-control-xl mb-3" type="text"
                    aria-label="item_code" tabindex="1" value="{{ old('item_code') }}" autocomplete="off">
                <div class="row align-items-end mb-3">
                    
                </div>

                <label for="name">Item Name</label>
                <input name="name" id="name" class="form-control form-control-xl mb-xl-3" type="text" aria-label="name"
                    readonly value="{{ old('name') }}">

                <label for="description">Description</label>
                <textarea name="description" id="description" class="form-control form-control-xl mb-xl-3"
                    readonly>{{ old('description') }}</textarea>

                <label for="s_price">Unit Price</label>
                <input name="s_price" id="s_price" class="form-control form-control-xl mb-xl-3" type="number" min="1"
                    aria-label="s_price" readonly value="{{ old('s_price') }}">

                <label for="s_quantity">Quantity</label>
                <input name="s_quantity" id="s_quantity" class="form-control form-control-xl mb-xl-3" type="number" min="1"
                    aria-label="s_quantity" tabindex="2" value="{{ old('s_quantity') }}" autocomplete="off">
                <button id="add-item" class="btn btn-button-submit text-white py-xl-2 px-xl-5" type="submit">Add
                    Item</button>
            </form>            
        </div>
        <div class="col-xl-9">
            <div class="row">
                <div class="col-xl-4">
                    <div id="reader"></div>
                </div>
                <div class="col-xl-auto ms-auto">
                    <button id="clear-table" class="btn btn-danger px-2 py-2 ms-auto">
                        <i class="fa-solid fa-circle-xmark"></i>
                        Clear Table</button>
                </div>
            </div>
            @if ($errors->any())
                @php
                    $message = 'Please Fix the errors below';
                    print_r($errors->all());
                @endphp
                <div class="py-3">
                    @include('components.error-message')
                </div>
            @endif
            @error('product_id')
                @include('components.error-message')
            @enderror
            @error('quantity')
                @include('components.error-message')
            @enderror
            <table id="products_list" class="table table-striped">
                <thead>
                    <tr>
                        <th scope="col">Code</th>
                        <th scope="col">Name</th>
                        <th scope="col">Description</th>
                        <th scope="col">Quantity</th>
                        <th scope="col">Unit Price</th>
                        <th scope="col">Total</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        echo $tbody_content;
                    @endphp
                </tbody>
            </table>
            @include('layouts.empty-table')
            <div class="row mt-3">
                <div class="col-xl-4 ms-auto">
                    <input type="hidden" name="supplier_search_id" id="supplier_search_id"
                        form="purchase-order" value="{{ old('supplier_search_id') }}">
                    @error('vendor')
                        @include('components.error-message')
                    @enderror
                    <label for="vendor">Vendor</label>
                    <input type="text" name="vendor" id="vendor" value="{{ old('vendor') }}" class="form-control mb-3"
                        form="purchase-order" autocomplete="off">

                    @error('company')
                        @include('components.error-message')
                    @enderror
                    <label for="company">Company</label>
                    <input type="text" name="company" id="company" value="{{ old('company') }}" class="form-control mb-3"
                        form="purchase-order" autocomplete="off">
                    @error('contact')
                        @include('components.error-message')
                    @enderror
                    <label for="contact">Contact Details</label>
                    <input type="text" name="contact" id="contact" value="{{ old('contact') }}" class="form-control mb-3"
                        form="purchase-order" autocomplete="off">
                    @error('address')
                        @include('components.error-message')
                    @enderror
                    <label for="address">Address</label>
                    <input type="text" name="address" id="address" value="{{ old('address') }}" class="form-control mb-3"
                        form="purchase-order" autocomplete="off">
                    @error('eta')
                        @include('components.error-message')
                    @enderror
                    <label for="eta">Leading Time</label>
                    <input type="text" name="eta" id="eta" value="{{ old('eta') }}" class="form-control mb-3"
                        form="purchase-order" autocomplete="off">
                </div>
                <div class="col-xl-4">                   
                    <div>
                        <b class="fs-5">Total</b>
                        <b class="fs-5 float-end" id="total">
                            <input type="hidden" name="total" value="{{ old('total') ?? '0.00' }}" form="purchase-order">
                            <span>{{ old('total') ?? '0.00' }}</span>
                        </b>
                    </div>
                    <input type="submit" value="Checkout" class="form-control btn btn-primary px-3 py-3 ms-auto mt-5"
                        form="purchase-order">
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @include('components.admin.content')
@endsection
