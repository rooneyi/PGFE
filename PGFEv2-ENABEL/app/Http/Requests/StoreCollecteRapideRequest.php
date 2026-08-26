<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\CollecteRapide;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreCollecteRapideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CollecteRapide::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $user = $this->user();
        $provedId = (int) ($user?->proved_id ?? 0);

        return [
            'sous_division_id' => [
                'required',
                'integer',
                Rule::exists('sous_divisions', 'id')->where('proved_id', $provedId),
            ],
            'school_year_id' => ['required', 'integer', 'exists:school_years,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'sous_division_id.required' => 'La sous-division est obligatoire.',
            'school_year_id.required' => 'L’année scolaire est obligatoire.',
        ];
    }

    public function provedId(): int
    {
        return (int) $this->user()->proved_id;
    }
}