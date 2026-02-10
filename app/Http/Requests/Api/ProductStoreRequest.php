<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ProductStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [

            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:1|max:10000000',
            'description' => 'required|string|max:255',
            'stock' => 'required|numeric|min:0|max:10000000',
            'category_id' => 'required|integer|exists:categories,id',
        ];
    }
}
