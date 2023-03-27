@extends('layouts.app')

@section('title')
    {{ $title }}
@endsection


@section('infile_style')
    <link rel="" href="css/app.css">

    <style>
        @page {
            margin: 0px;
        }

        body {
            margin: 0px;
        }

        * {
            font-family: 'Helvetica',Helvetica, sans-serif;
            color: #5a4d61;
            font-size:1rem;
        }

        .gutter {
            padding: 10px;
        }

        #header {
            line-height: 1;
            vertical-align: middle;
        }

        .icon {
            height: 10px;
            width: 15px;
            display: inline;
            line-height: 1;
            vertical-align: middle;
            
        }

        .icon+b {
            line-height: 1;
            vertical-align: middle;
        }

    </style>
@endsection

@section('content')
    <div class="col-xl-2 mx-auto px-0 bg-white text-black" style="min-height: 50vh">
        <br>
        <div id="header" class="text-center align-center mb-xl-5 w-25 mx-auto">
            <img src="img/logo.png" id="logo" alt="maemaestore logo" class="img-fluid"  height="100" width="150"> </div>
            <div><b>JG Ramos Drugmart</b>
            <br>
            <small class="p-0 m-0 mt-1 d-block"><b>Villasis, Pangasinan</b></small>
        </div>
        <br>
        <small class="p-0 m-0 pt-3 d-block"><b>Transaction ID: {{ $transaction_id }}</b></small>
        <br>
       
        <small class="p-0 m-0 d-block"><b>Serial Number: {{ $customer->serial_number }}</b></small>
        <br>
        <small class="p-0 m-0 d-block"><b>Date: {{ date('Y-m-d H:i', strtotime($items[0]->created_at)) }}</b></small>
        <br>
        <small class="p-0 m-0 d-block"><b>Cashier: {{ $cashier_name }}</b></small>
        <br>
        <small class="p-0 m-0 d-block"><b>Customer: {{ $customer->customer_name }}</b></small>
        <br>
       
        <small class="p-0 m-0 d-block"><b>ID Number: {{ $customer->customer_contact_detail }}</b></small>
        <br>
    
        <div class="py-3 my-0">
            <hr class="my-0 py-0 pb-1">
        </div>
        <div>
        <table class="table my-0">
            <tbody>
                @php
                    $total = 0;
                @endphp
                @foreach ($items as $item)
                    <tr>
                        <ekis class="p-0 m-0"><b>{{ $item->p_name }} x {{ $item->quantity }}: </b></ekis>
        </tr>
        <tr>
                        <ekis class="p-0 m-0"><b>{{ $item->selling_price * $item->quantity }}</b></ekis>
                       
        </tr>
    </div>
        <div class="py-3 my-0">
            <hr class="my-0 py-0 pb-10">
        </div>
                    @php
                        $total += $item->selling_price * $item->quantity;
                    @endphp
                @endforeach
                <tr>
                    <small class="pt-5 p-0 m-0"><b>Total:{{ sprintf('%.2f', $total) }}</b></small>
                    
                </tr>
                @php
                    $discount = $total * $item->senior_discount;
                    $discounted_total = $total - $discount;
                @endphp
                @if ($item->senior_discount)
                    <tr>
                        <small class="pt-3 p-0 m-0"><b>Discount: {{ sprintf('%.2f', negativeToZero($discount)) }} </b></small>
                      
                        </td>
                    </tr>
                    <tr>
                        <small class="pt-5 p-0 m-0"><b>Discounted Total:{{ sprintf('%.2f', $discounted_total) }}</b></small>
                        
                    </tr>
                @endif
                
                <tr>
                    <small class="p-0 m-0"><b>Amount Paid:{{ sprintf('%.2f', $item->amount_paid) }}</b></small>
                  
                </tr>
                <tr>
                    <small class="pt-3 p-0 m-0"><b>Change:{{ sprintf('%.2f', negativeToZero($item->amount_paid - $discounted_total)) }} </b></small>
                   
                    </td>
                </tr>                
            </tbody>
        </table>
    </div>
@endsection
