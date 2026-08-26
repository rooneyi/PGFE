<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SousDivisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sousDivision = $this->route('sous_division');
        $provedId = $this->input('proved_id', $sousDivision?->proved_id);

        return [
            'proved_id' => ['required', 'exists:proveds,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('sous_divisions', 'code')
                    ->where(fn ($q) => $q->where('proved_id', $provedId))
                    ->ignore($sousDivision?->id),
            ],
        ];
    }
}
