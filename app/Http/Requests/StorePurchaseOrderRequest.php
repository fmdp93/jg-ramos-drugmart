<?php

namespace App\Http\Requests;

use App\Http\Controllers\InventoryController;
use App\Http\Traits\ValidationTrait;
use Illuminate\Http\Request;
use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
{
    use ValidationTrait;
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(Request $request)
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {                
        return [
            'vendor' => 'required',
            'contact' => 'required',
            'company' => 'required',
            'address' => 'required',
            'eta' => 'required|date',
        ];
    }
}
