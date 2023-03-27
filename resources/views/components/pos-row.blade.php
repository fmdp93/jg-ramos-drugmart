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
        <input type="number" name="quantity[]" value="{{ $quantity }}" form="{{ $form }}" min="1" required>
    </td>
    <td class="price">
        <input type="hidden" name="price[]" value="{{ sprintf("%.2f", $price) }}" form="{{ $form }}">
        {{ sprintf("%.2f", $price) }}
    </td>
    <td class="subtotal">
        <input type="hidden" name="t_subtotal[]" value="{{ sprintf("%.2f", $subtotal) }}" form="{{ $form }}">
        {{ sprintf("%.2f", $subtotal) }}</td>
    <td>
        <i class="fa-solid fa-xmark text-danger fa-2x delete-item" title="Void Item" data-bs-toggle="modal" data-bs-target="#pin-modal"></i>
    </td>
</tr>
