@foreach ($io_products as $product)
    <tr>
        <form action="{{ url('/inventory/order-received') }}" method="POST" class="order-received-form px-1">
            @csrf
            <input type="hidden" name="transaction_id" class="transaction_id" value="{{ $product->io_id }}">
            <input type="hidden" name="product_id" class="product_id" value="{{ $product->p_id }}">
            <td>
                {{ $product->p_item_code }}
                <input type="hidden" name="item_code" class="item_code" value="{{ $product->p_item_code }}">
            </td>
            <td>{{ $product->p_name }}</td>
            <td>{{ $product->c_name }}</td>
            <td>{{ Str::limit($product->p_desc, 50) }}</td>
            <td class="text-end">
                {{ sprintf("%.2f",$product->base_price) }}
            </td>
            <td class="text-end">
                {{ sprintf("%.2f", $product->base_price * $product->markup / 100) }}
            </td>
            <td class="text-end">
                {{ sprintf("%.2f", $product->selling_price) }}
            </td>
            <td class="fit">
                <input type="number" name="quantity" class="quantity form-control"
                    value="{{ $product->io2p_quantity }}">
            </td>
            <td class="">
                <input type="text" name="expiration_date" class="expiration_date form-control ms-auto"
                    value="{{ $product->expiration_date }}">
            </td>
            
            <td class="text-end">                
                <input type="number" name="price" value="{{ sprintf('%.2f', $product->io2p_price) }}" class="price form-control ms-auto">
            </td>
            <td class="text-end subtotal">
                {{ sprintf('%.2f', $product->io2p_price * $product->io2p_quantity) }}
            </td>
            <td class="text-center text-nowrap">
                <input type="hidden" name="io2p_id" value="{{ $product->io2p_id }}" class="io2p_id">
                <button type="submit" class="order-received btn btn-success w-100">Order Received</button>
            </td>
        </form>
    </tr>
@endforeach
