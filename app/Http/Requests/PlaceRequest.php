<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class PlaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('places')
                    ->where(function ($query) {
                        $query->where('city_id', $this->input('city_id'))
                            ->where('state_id', $this->input('state_id'));
                    })
                    ->ignore($this->place),
            ],
            'city' => ['required', 'string', 'exists:cities,nome'],
            'state' => ['required', 'string', 'exists:states,sigla'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do lugar é obrigatório.',
            'name.string' => 'O nome do lugar deve ser uma string.',
            'name.max' => 'O nome do lugar não pode ultrapassar 255 caracteres.',

            'city.required' => 'O nome da cidade é obrigatório.',
            'city.string' => 'O nome da cidade deve ser uma string.',
            'city.exists' => 'A cidade informada não existe.',

            'state.required' => 'O nome do estado é obrigatório.',
            'state.string' => 'O nome do estado deve ser uma string.',
            'state.exists' => 'O estado informado não existe.',
        ];
    }

    protected function failedValidation(Validator|\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'errors' => $validator->errors()
        ], 422));
    }
}
