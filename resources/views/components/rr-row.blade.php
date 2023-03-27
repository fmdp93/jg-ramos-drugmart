<tr>    
    <td>
        <input type="hidden" name="product_id[]" value="{{ $p_id }}" form="{{ $form }}">
        <input type="hidden" name="t_item_code[]" value="{{ $code }}" form="{{ $form }}">        
        {{ $code }}
    </td>
    <td>
        <input type="hidden" name="t_name[]" value="{{ $name }}" form="{{ $form }}">        
        {{ $name }}</td>
    <td>
        <input type="hidden" name="t_description[]" value="{{ $description }}" form="{{ $form }}">        
        {{ $description }}</td>
    <td>        
        <input type="number" name="quantity[]" value="{{ $quantity }}" form="{{ $form }}" min="" >
    </td>
    <td class="price">
        <input type="hidden" name="price[]" value="{{ $price }}" form="{{ $form }}">
        {{ sprintf("%.2f", $price) }}
    </td>
    <td class="subtotal">
        <input type="hidden" name="t_subtotal[]" value="{{ $price }}" form="{{ $form }}">
        {{ sprintf("%.2f", $subtotal) }}</td>
    <td>
        <i class="fa-solid fa-xmark text-danger fa-2x delete-item" title="Delete Item"></i>
    </td>
</tr>
