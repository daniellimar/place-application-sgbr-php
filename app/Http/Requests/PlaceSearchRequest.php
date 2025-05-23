<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlaceSearchRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|min:1|max:255',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'O parâmetro "name" é obrigatório para a busca.',
            'name.string' => 'O parâmetro "name" deve ser uma string válida.',
            'name.min' => 'O parâmetro "name" deve conter ao menos 1 caractere.',
            'name.max' => 'O parâmetro "name" pode conter no máximo 255 caracteres.',
        ];
    }
}
