<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\State;
use App\Models\City;
use Illuminate\Support\Facades\DB;

class StateSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            $statesJson = Storage::disk('local')->get('data/state.json');
            $states = json_decode($statesJson, true);

            $stateIdBySigla = [];

            foreach ($states as $state) {
                $stateModel = State::updateOrCreate(
                    ['sigla' => $state['sigla']],
                    [
                        'nome' => $state['nome'],
                        'regiao_nome' => $state['regiao']['nome'] ?? null,
                    ]
                );

                $stateIdBySigla[$state['sigla']] = $stateModel->id;
            }

            $citiesJson = Storage::disk('local')->get('data/city.json');
            $cities = json_decode($citiesJson, true);

            foreach ($cities['data'] as $city) {
                $stateId = $stateIdBySigla[$city['Uf']] ?? null;

                if ($stateId) {
                    City::updateOrCreate(
                        ['nome' => $city['Nome'], 'state_id' => $stateId],
                        []
                    );
                } else {
                    echo "Estado não encontrado para sigla: {$city['Uf']} (cidade: {$city['Nome']})" . PHP_EOL;
                }
            }
        });

        echo "Estados e cidades importados com sucesso!" . PHP_EOL;
    }
}
