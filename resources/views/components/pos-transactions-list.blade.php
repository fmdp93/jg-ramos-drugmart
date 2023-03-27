@php 
    use App\Http\Controllers\POSController;
@endphp
@foreach ($transactions as $transaction)
    <tr>
        <td>{{ $transaction->t_id }}</td>
        <td>{{ date('F j, Y', strtotime($transaction->t_date)) }}</td>
        <td>{{ $transaction->p_name }}</td>
        <td>{{ $transaction->description }}</td>
        <td>{{ $transaction->pt2p_quantities }}</td>
        <td>{{ sprintf('%.2f', $transaction->pt2p_price_total) }}</td>
        <td>{{ sprintf('%.2f', $transaction->amount_paid) }}</td>
        <td>{{ sprintf('%.2f', negativeToZero($transaction->amount_paid - $transaction->pt2p_price_total)) }}</td>
        <td>
        </td>
    </tr>
@endforeach
