@extends('layouts.app')

@section('header_scripts')
    <script src="{{ asset('js/scope/inventory-orders.js') }}" defer type="module"></script>
@endsection

@section('admin_content')
    <div id="inventory_orders" class="row px-xl-5">
        @include('layouts.heading')
        <div class="col-xl-12">
            <a href="{{ url('/inventory/purchase-order') }}" class="btn btn-success mb-xl-3 text-primary">
                <i class="fa fa-plus-circle"></i> Purchase Order
            </a>
            <a href="{{ route('inventory_order_history') }}" class="btn btn-success mb-xl-3 text-primary">
                <i class="fa fa-clock"></i> History
            </a>
            <a href="{{ route('back_order') }}" class="btn btn-success mb-xl-3 text-primary">
                <i class="fa-solid fa-list-check"></i> Back Order
            </a>
            <table id="inventory_order_list" class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Vendor</th>
                        <th>Company</th>
                        <th>Contact</th>
                        <th>Address</th>
                        <th>Leading Time</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @include('components.admin.inventory-orders-list')
                </tbody>
            </table>
            {{-- <input type="text" name="expiration_date[]" class="expiration_date form-control ms-auto">
            <input type="text" name="expiration_date[]" class="expiration_date form-control ms-auto">
            <input type="text" name="expiration_date[]" class="expiration_date form-control ms-auto"> --}}
            <div id="details-modal">
                <div class="modal fade" id="detailsModalContent" tabindex="-1"
                    aria-labelledby="detailsModalContentLabel" aria-hidden="true">
                    <div class="modal-dialog modal-fullscreen p-5">
                        <div class="modal-content">                            
                            <div class="modal-header">
                                <h5 class="modal-title" id="detailsModalContentLabel" class="d-block">
                                    Ordered Products'
                                    List</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <table class="table table-striped">
                                    <thead>
                                        <th>Item Code</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Description</th>
                                        <th class="text-end">Base Price</th>
                                        <th class="text-end">Markup Price</th>
                                        <th class="text-end">Selling Price</th>
                                        <th class="fit">Quantity</th>
                                        <th class="">Expiration</th>
                                        <th class="text-end">Ordered Price</th>
                                        <th class="text-end">Total</th>
                                        <th class="text-center">Action</th>
                                    </thead>
                                    <tbody class="align-middle">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @empty(count($inventory_orders))
                @include('layouts.empty-table')
            @endempty
        </div>
    </div>
@endsection

@section('content')
    @include('components.admin.content')
@endsection
