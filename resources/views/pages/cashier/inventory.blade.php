@extends('layouts.inventory')

@section('layout_header_scripts')
    <script src="{{ asset('js/scope/cashier-inventory.js') }}" defer type="module"></script>
@endsection

@section('inventory_headings')
    <div class="row mb-xl-4">
        <div class="col-xl-2">
            <label for="search">Search Product</label>
            @include('components.search')
        </div>
        
        <div class="col-xl-auto ms-auto">
            <label for="expiry" class="pe-1">Expiry</label>
            <select name="expiry" form="{{ $form_id ?? '' }}" id="expiry" class="form-select">
                <option value="latest" {{ $expiry == 'latest' ? 'selected' : '' }}>Latest</option>
                <option value="oldest" {{ $expiry == 'oldest' ? 'selected' : '' }}>Oldest</option>
            </select>
        </div>
        <div class="col-xl-auto">
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
@endsection
