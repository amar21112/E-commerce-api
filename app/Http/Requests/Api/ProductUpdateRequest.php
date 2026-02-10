<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ProductUpdateRequest extends FormRequest
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
        if($this->method() == 'PATCH') {
            return [
                'name' => 'sometimes|string|max:255',
                'price' => 'sometimes|numeric|min:1|max:10000000',
                'description' => 'sometimes|string|max:255',
                'stock' => 'sometimes|numeric|min:0|max:10000000',
                'category_id' => 'sometimes|integer|exists:categories,id',
            ];
        }

        return [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:1|max:10000000',
            'description' => 'required|string|max:255',
            'stock' => 'required|numeric|min:0|max:10000000',
            'category_id' => 'required|integer|exists:categories,id',
        ];
    }
}
