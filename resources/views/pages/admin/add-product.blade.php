@php
use App\Http\Controllers\CategoryController;
@endphp

@extends('layouts.app')

@section('header_scripts')
    <script>const default_markup = {{ $markup }}</script>
    <script src="{{ asset('js/scope/add_product.js') }}" defer type="module"></script>
@endsection

@section('admin_content')
    <div class="col px-xl-5 mb-xl-3">
        @include('layouts.heading')
        <div class="col-xl-5">
            <a href="{{ action([CategoryController::class, 'index']) }}"
                class="btn btn-success py-xl-2 px-xl-3 mb-xl-3 text-primary"><i class="fa fa-plus-circle"></i> Add
                Category</a>
            <form action="{{ url('/product/add') }}" method="POST">
                @csrf
                @if ($errors->any())
                    {{ print_r($errors->all()) }}
                @endif
                @error('item_code')
                    @include('components.error-message')
                @enderror
                <div class="row align-items-end mb-3">
                    <div class="col-xl-5 pe-1">
                        <label for="item_code">Item Code</label>
                        <input name="item_code" id="item_code" class="form-control form-control-xl" type="text"
                            aria-label="item_code" value="{{ old('item_code') }}">
                    </div>
                    <div class="col-xl-1 px-0">
                        <button id="new_item_code" class="btn btn-success"><i class="fa-solid fa-circle-plus"></i></button>
                    </div>
                    @error('name')
                    @include('components.error-message')
                @enderror
                    <div class="col-xl-5 pe-1">
                    <label for="name">Item Name</label>
                <input name="name" id="name" class="form-control form-control-xl mr-xl-3" type="text" aria-label="name"
                    value="{{ old('name') }}">
                    </div>
                    
                </div>


              
             

                @error('description')
                    @include('components.error-message')
                @enderror
                <div class="row">
                    <div class="col-xl-5 pe-1">
                <label for="description">Description</label>
                <textarea name="description" id="description"
                    class="form-control form-control-xl mb-xl-3">{{ old('description') }}</textarea>
                    </div>  
                @error('category_id')
                    @include('components.error-message')
                @enderror
                <div class="col-xl-5 pe-1">
                <label for="category_id">Category</label>
                @if (count($categories) === 0)
                    <a href="{{ action([CategoryController::class, 'index']) }}"
                        class="d-block py-xl-3 text-decoration-underline text-danger"><i class="fa fa-plus-circle"></i> Add
                        Category first</a>
                @else
                    <select name="category_id" id="category_id" class="form-select mb-xl-3">
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}</option>
                        @endforeach
                    </select>
                @endif
                </div>
                </div>

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
                    <div class="col-xl-4">
                        <label for="base_price">Base Price</label>
                        <input name="base_price" id="base_price" class="form-control form-control-xl mb-xl-3" type="number"
                            min="0" step=".01" aria-label="base_price" value="{{ old('base_price') }}"
                            {{ getInputFloatPattern() }}>
                    </div>                    
                    <div class="col-xl-4">
                        <label for="markup">Markup Price</label>
                        <div class="input-group mb-xl-3">
                            <input name="markup" id="markup" class="form-control form-control-xl" type="number" min="12" max="30" step=".01" 
                                aria-label="markup" aria-describedby="markup-addon" value="{{ $markup }}"
                                {{ getInputFloatPattern() }}>
                            <span class="input-group-text" id="markup-addon">%</span>
                        </div>
                    </div>
                    <div class="col-xl-4">
                        <label for="selling_price">Selling Price</label>
                        <input name="selling_price" id="selling_price" class="form-control form-control-xl mb-xl-3"
                            type="number" step=".01" aria-label="selling_price" value="{{ old('selling_price') }}"
                            >
                    </div>
                </div>

                @error('unit')
                    @include('components.error-message')
                @enderror
                <div class="row">
                    <div class="col-xl-3">
                <label for="unit">Unit</label>
                <select name="unit" id="unit" class="form-select mb-xl-3">
                    <option value="piece" {{ old('unit') == 'piece' ? 'selected' : '' }}>Piece</option>
                    <option value="liter" {{ old('unit') == 'liter' ? 'selected' : '' }}>Liter</option>
                    <option value="Box" {{ old('unit') == 'Box' ? 'selected' : '' }}>Box</option>
                </select>
</div>
                @error('stock')
                    @include('components.error-message')
                @enderror
                <div class="col-xl-4">
                <label for="stock">Max Stocks</label>
                <input name="stock" id="stock" class="form-control form-control-xl mb-xl-3" type="number" min="0"
                    placeholder="e.g. 100" aria-label="stock" value="{{ old('stock') }}">
</div>
                @error('expiration_date')
                    @include('components.error-message')
                @enderror
                <div class="col-xl-4">
                <label for="expiration_date">Expiration Date</label>
                <input name="expiration_date" id="expiration_date" class="form-control form-control-xl mb-xl-3" type="text"
                    placeholder="2030-12-30" aria-label="expiration_date" value="{{ old('expiration_date') }}"
                    autocomplete="off">
                    </div>
                </div>


                @error('supplier')
                @include('components.error-message')
                @enderror
                <div class="row">
                    <div class="col-xl-6">
                <label for="supplier">Supplier</label>
                <select name="supplier" id="supplier" class="form-select mb-3">
                    <option value="">---Choose a supplier---</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" 
                            {{ old('supplier') == $supplier->id ? 'selected' : '' }}>
                            {{ sprintf("%s, %s", ucwords($supplier->vendor), ucwords($supplier->company_name)) }}
                        </option>
                    @endforeach
                </select>
</div>

                    <div class="col-xl-6">
                <label for="vendor">Vendor</label>
                <input type="text" name="vendor" id="vendor" value="{{ old('vendor') }}" class="form-control mb-3"
                    autocomplete="off" readonly>
                    </div>
                    </div>
                    <div class="row">
                    <div class="col-xl-4">
                <label for="company">Company</label>
                <input type="text" name="company" id="company" value="{{ old('company') }}" class="form-control mb-3"
                    autocomplete="off" readonly>
                    </div>
                    <div class="col-xl-4">
                <label for="contact">Contact Details</label>
                <input type="text" name="contact" id="contact" value="{{ old('contact') }}" class="form-control mb-3"
                    autocomplete="off" readonly>
                    </div>
                    <div class="col-xl-4">
                <label for="address">Address</label>
                <input type="text" name="address" id="address" value="{{ old('address') }}" class="form-control mb-3"
                    autocomplete="off" readonly>
                    </div>
                    </div>
                @error('inv_stock')
                    @include('components.error-message')
                @enderror
                <label for="inv_stock">Current/Remaining Stocks (Optional)</label>
                <input name="inv_stock" id="inv_stock" class="form-control form-control-xl mb-xl-3" type="number" min="0"
                    placeholder="e.g. 100" aria-label="inv_stock" value="{{ old('inv_stock') }}">

                <button class="float-end btn btn-button-submit text-white py-xl-2 px-xl-5" type="submit"
                    @if (count($categories) === 0) disabled @endif>Add Item</button>
            </form>
        </div>
        <div class="col-xl-4">
            <div id="reader" class="w-75"></div>
        </div>
    </div>
@endsection

@section('content')
    @include('components.admin.content')
@endsection
