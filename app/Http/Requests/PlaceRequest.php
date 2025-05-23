<?php

namespace App\Http\Requests;

use App\Models\{City, State};
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
            'city' => ['required', 'string'],
            'state' => ['required', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $cityInput = mb_strtolower(trim($this->input('city')));
            $stateInput = mb_strtoupper(trim($this->input('state')));

            $state = State::where('sigla', $stateInput)->first();

            if (!$state) {
                $validator->errors()->add('state', 'O estado informado não existe.');
                return;
            }

            $city = City::whereRaw('LOWER(nome) = ?', [$cityInput])
                ->where('state_id', $state->id)
                ->first();

            if (!$city) {
                $validator->errors()->add('city', 'A cidade informada não existe nesse estado.');
                return;
            }

            $this->merge([
                'city_id' => $city->id,
                'state_id' => $state->id,
            ]);
        });
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do lugar é obrigatório.',
            'name.string' => 'O nome do lugar deve ser uma string.',
            'name.max' => 'O nome do lugar não pode ultrapassar 255 caracteres.',
            'name.unique' => 'Já existe um local com este nome para esta cidade e estado.',

            'city.required' => 'O nome da cidade é obrigatório.',
            'city.string' => 'O nome da cidade deve ser uma string.',

            'state.required' => 'O nome do estado é obrigatório.',
            'state.string' => 'O nome do estado deve ser uma string.',
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
