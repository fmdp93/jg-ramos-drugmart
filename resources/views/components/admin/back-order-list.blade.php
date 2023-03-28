@foreach ($products as $product)
    <tr>
        <td>{{ $product->transaction_id }}</td>
        <td>{{ $product->item_code }}</td>
        <td>{{ $product->p_name }}</td>
        <td>{{ $product->base_price }}</td>
        <td>{{ $product->markup }}%</td>
        <td>{{ $product->price }}</td>
        <td>{{ $product->expiration_date }}</td>
        <td>{{ date_format(date_create($product->date_received), "Y-m-d H:i") }}</td>
        <td>{{ $product->back_order_quantity }}</td>
    </tr>
@endforeach
