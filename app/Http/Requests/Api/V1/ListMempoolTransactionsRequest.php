<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ListMempoolTransactionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'offset' => ['sometimes', 'integer', 'min:0', 'max:500000'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:25'],
        ];
    }
}
