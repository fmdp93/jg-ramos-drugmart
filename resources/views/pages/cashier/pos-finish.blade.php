@php
use App\Http\Controllers\POSController;
@endphp

@extends('layouts.app')

@section('header_scripts')
    <script>
        const search_url = '{{ route('search_pos_transaction') }}';
    </script>
    <script src="{{ asset('js/scope/search.js') }}" defer></script>
    <script src="{{ asset('js/scope/pos_transaction.js') }}" defer type="module"></script>
    <script defer>
        $(function() {
            if ({{ $transaction_id }} !== null) {
                let $receipt_counter = $("#receipt_counter");
                let x = parseInt($receipt_counter.html());
                let receipt_counter_interval = setInterval(function() {                                        
                    if (x == 0) {
                        clearInterval(receipt_counter_interval);
                        
                    window.location.href =
                        "{{ route('receipt', ['transaction_id' => $transaction_id]) }}";
                    }                    

                    $receipt_counter.html(x--);
                }, 1000);
            }
        });
    </script>
@endsection

@section('title')
    {{ $title }}
@endsection

@section("{$user}_content")
    <div class="row px-xl-5">
        @if (!empty($transaction_id))
                <h1 class="mb-3">Your receipt will be ready in <span id="receipt_counter">3</span></h1>
            @endif
        @include('layouts.heading')
        <div class="col-xl-12">            
            <div class="row">
                <div class="col-xl-9">
                    @if (request()->input('transaction_id'))
                        <a href="{{ action([POSController::class, 'index']) }}"
                            class="btn btn-success mb-xl-3 text-primary"><i class="fa fa-plus-circle"></i> Make another
                            transaction</a>
                        {{-- <a href="{{ action([POSController::class, 'receipt'], ['transaction_id' => $transaction_id]) }}"
                            class="btn btn-success mb-xl-3 text-primary"><i class="fa fa-print"></i> Print Receipt</a> --}}
                    @endif
                </div>
                @if (Auth::user()->role_id == 1)
                    <div class="col-xl-3 ms-auto">
                        @include('components.search')
                    </div>
                @endif
            </div>
        </div>
        @if (Auth::user()->role_id == 1)
            <div class="col-xl-12">
                <table id="pos_transaction_list" class="table table-striped">
                    <thead>
                        <tr>
                            <th scope="col">Trans. #</th>
                            <th scope="col">Date</th>
                            <th scope="col">Item</th>
                            <th scope="col">Description</th>
                            <th scope="col">Quantity</th>
                            <th scope="col">Price</th>
                            <th scope="col">Amount Paid</th>
                            <th scope="col">Change</th>
                            
                        </tr>
                    </thead>
                    <tbody>
                        @include('components.pos-transactions-list')
                    </tbody>
                </table>
                <div id="pages">
                    {{ $transactions->links() }}
                </div>
                @empty($transactions)
                    @include('layouts.empty-table')
                @endempty
            </div>
        @endif
    </div>
@endsection

@section('content')
    @include("components.{$user}.content")
@endsection
