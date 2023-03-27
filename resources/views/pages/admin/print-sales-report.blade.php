@extends('layouts.app')

@section('infile_style')
    <link rel="stylesheet" href="css/app.css">

    <style>
        body {
            background: #fff;
        }

        * {
            font-family: 'Courier New', Courier, monospace;
            color: #5a4d61;
        }

        .gutter {
            padding: 12px;
        }

        #header {
            line-height: 1;
            vertical-align: middle;
        }

        .icon {
            height: 17px;
            width: 20px;
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
    <div class="row">
        <div class="col-xl-12 px-xl-5">
            <div class="row pb-5 pt-5">
                <div class="col-xl-12">
                    <p class="pdf-heading">{{ $heading }}</p>
                </div>
                <div class="col-xl-8">
                    @switch(request()->input('date_filter'))
                        @case('daily')
                        <p class="fs-5">Daily</p>
                            <p class="fs-5">List for: {{ $from }}</p>
                        @break

                        @case('weekly')
                            <p class="fs-5">Weekly</p>
                        <p class="fs-5">List for: {{ $from }} to {{ $to }}</p>
                    @break 
                        @case('monthly')
                        <p class="fs-5">Monthly</p>
                            <p class="fs-5">List for: {{ date('F', strtotime($from)) }}
                                {{ date('Y', strtotime($to)) }}</p>
                        @break

                        @case('yearly')
                        <p class="fs-5">Yearly</p>
                            <p class="fs-5">List for: {{ date('Y', strtotime($to)) }}</p>
                        @break

                        @default
                       
                            <p class="fs-5">List from: {{ $from }} to {{ $to }}</p>
                    @endswitch
                </div>
                <div class="float-right w-50">
                    <table class="table border">
                        <tbody>
                            <tr>
                                <td class="fs-5">Total Items</td>
                                <td class="fs-5 text-end"> {{ $total_items }}</td>
                            </tr>
                            <tr>
                                <td class="fs-5">Total Sales:</td>
                                <td class="fs-5 text-end"> {{ number_format($total_sales, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="fs-5">Total Price:</td>
                                <td class="fs-5 text-end"> {{ number_format($total_price, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="fs-5">Profit:</td>
                                <td class="fs-5 text-end"> {{ number_format($profit, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>

                </div>
            </div>
            <div class="row">
                <div class="col-xl-12">
                    <table id="products_list" class="table">
                        <thead>
                            <tr>
                                <th class="pe-3 text-end" scope="col">Trans. #</th>
                                <th scope="col">Date</th>
                                <th scope="col">Item</th>
                                <th scope="col">Description</th>
                                <th class="pe-3 text-end" scope="col">Quantity</th>
                                <th class="pe-3 text-end" scope="col">Price</th>
                                <th class="pe-3 text-end" scope="col">Amount Paid</th>
                                <th class="pe-3 text-end" scope="col">Change</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transactions as $transaction)
                                <tr>
                                    <td class="pe-3 text-end">{{ $transaction->t_id }}</td>
                                    <td class="text-nowrap pe-3">{{ date('F j, Y', strtotime($transaction->t_date)) }}</td>
                                    <td>{{ $transaction->p_name }}</td>
                                    <td>{{ Str::limit($transaction->description, 40) }}</td>
                                    <td class="pe-3 text-end">{{ $transaction->pt2p_quantities }}</td>
                                    <td class="pe-3 text-end">{{ sprintf('%.2f', $transaction->pt2p_price_total) }}</td>
                                    <td class="pe-3 text-end">{{ sprintf('%.2f', $transaction->amount_paid) }}</td>
                                    @php
                                        $change = $transaction->amount_paid - $transaction->pt2p_price_total;
                                    @endphp
                                    <td class="pe-3 text-end">{{ sprintf('%.2f', negativeToZero($change)) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
@endsection