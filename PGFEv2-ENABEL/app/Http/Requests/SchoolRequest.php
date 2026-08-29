<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SchoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $phone = $this->input('phone_number');

        if ($phone === null || (is_string($phone) && trim($phone) === '')) {
            $this->merge(['phone_number' => null]);

            return;
        }

        $this->merge([
            'phone_number' => self::normalizeDrcPhone((string) $phone),
        ]);
    }

    /**
     * Accept common DRC formats and normalize to +243XXXXXXXXX.
     * Examples: +243812345678, 243 812 345 678, 0812 345 678
     */
    public static function normalizeDrcPhone(string $phone): string
    {
        $clean = preg_replace('/[\s\-().]/', '', trim($phone)) ?? '';

        if (preg_match('/^\+243\d{9}$/', $clean) === 1) {
            return $clean;
        }

        if (preg_match('/^243\d{9}$/', $clean) === 1) {
            return '+'.$clean;
        }

        if (preg_match('/^0\d{9}$/', $clean) === 1) {
            return '+243'.substr($clean, 1);
        }

        return $clean;
    }

    public function rules(): array
    {
        $school = $this->route('school');
        $schoolId = is_object($school) ? $school->id : $school;

        return [
            'province_id' => ['required', 'exists:provinces,id'],
            'country_id' => ['required', 'exists:countries,id'],
            'city' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255', Rule::unique('schools', 'name')->ignore($schoolId)],
            'address' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'string', 'max:255'],
            'longitude' => ['nullable', 'string', 'max:255'],
            'phone_number' => [
                'nullable',
                'string',
                'regex:/^\+243[0-9]{9}$/',
                Rule::unique('schools', 'phone_number')->ignore($schoolId),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('schools', 'email')->ignore($schoolId),
            ],
            'type_id' => ['required', 'exists:types,id'],
            'sous_division_id' => ['nullable', 'integer', 'exists:sous_divisions,id'],
            'logo' => ['nullable', 'image', 'max:2048'], // 2MB max
        ];
    }

    public function messages(): array
    {
        return [
            'province_id.required' => 'La province est requise',
            'province_id.exists' => 'La province sélectionnée n\'existe pas',
            'country_id.required' => 'Le pays est requis',
            'city.required' => 'La ville est requise',
            'name.required' => 'Le nom de l\'école est requis',
            'name.unique' => 'Cette école existe déjà',
            'address.required' => 'L\'adresse est requise',
            'phone_number.regex' => 'Numéro invalide. Utilisez +243 suivi de 9 chiffres (ex. +243812345678) ou le format local 0XXXXXXXXX.',
            'phone_number.unique' => 'Ce numéro de téléphone existe déjà',
            'email.email' => 'L\'email n\'est pas valide',
            'email.unique' => 'Cet email existe déjà',
            'type_id.required' => 'Le type est requis',
            'sous_division_id.exists' => 'La sous-division sélectionnée n\'existe pas',
            'logo.image' => 'Le logo doit être une image',
            'logo.max' => 'Le logo ne doit pas dépasser 2MB',
        ];
    }
}
