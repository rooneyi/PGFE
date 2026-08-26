<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\CollecteRapide;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateCollecteRapideStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var CollecteRapide $collecte */
        $collecte = $this->route('collecte_rapide');

        return $this->user()?->can('update', $collecte) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'data' => ['nullable', 'array'],
            'advance' => ['nullable', 'boolean'],
        ];
    }

    public function stepNumber(): int
    {
        return (int) $this->route('step');
    }

    public function shouldAdvance(): bool
    {
        return (bool) $this->boolean('advance');
    }
}
