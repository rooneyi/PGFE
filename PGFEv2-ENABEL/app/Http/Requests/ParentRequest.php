<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ParentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');
        $parentId = $this->route('parent')?->id ?? $this->parent?->id;

        $phoneRules = [
            'required',
            'string',
            'max:255',
            'regex:/^\+243[0-9]{9}$/',
        ];

        if ($isUpdate) {
            $phoneRules[] = Rule::unique('parents', 'phone_number')->ignore($parentId);
        }

        $emailRules = ['nullable', 'email', 'max:255'];
        if ($isUpdate) {
            $emailRules[] = Rule::unique('parents', 'email')->ignore($parentId);
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'genre' => ['required', 'string', 'in:Masculin,Féminin,Non spécifié'],
            'identity_card' => ['nullable', 'string', 'max:255'],
            'phone_number' => $phoneRules,
            'email' => $emailRules,
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom est requis',
            'firstname.required' => 'Le prénom est requis',
            'lastname.required' => 'Le postnom est requis',
            'genre.required' => 'Le genre est requis',
            'genre.in' => 'Le genre doit être Masculin, Féminin ou Non spécifié',
            'phone_number.required' => 'Le numéro de téléphone est requis',
            'phone_number.unique' => 'Ce numéro de téléphone existe déjà',
            'phone_number.regex' => 'Le format du numéro doit être +243 suivi de 9 chiffres',
        ];
    }
}
