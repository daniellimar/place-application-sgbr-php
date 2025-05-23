<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\{PlaceSearchRequest, PlaceRequest};
use App\Http\Resources\{PlaceResource};
use App\Models\{City, State, Place};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class PlaceController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 15);
            $places = Place::paginate($perPage);

            return PlaceResource::collection($places);
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao listar os locais.', $e);
        }
    }

    public function store(PlaceRequest $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $validated = $request->validated();
                $state = $this->getStateBySigla($validated['state']);
                $city = $this->getCityByName($validated['city'], $state);

                $place = Place::create([
                    'name' => $validated['name'],
                    'city_id' => $city->id,
                    'state_id' => $state->id,
                ]);

                return new PlaceResource($place);
            });

        } catch (QueryException $e) {
            if ($e->getCode() === '23505') {
                return $this->conflictResponse('Já existe um local com este nome para esta cidade e estado.');
            }
            return $this->errorResponse('Erro ao criar o local.', $e);
        }
    }

    public function show(string $id)
    {
        try {
            $place = Place::with(['city', 'state'])->findOrFail($id);
            return new PlaceResource($place);
        } catch (QueryException $e) {
            if ($e->getCode() === '22P02') {
                return $this->badRequestResponse('ID fornecido é inválido. Certifique-se de que é um UUID válido.');
            }
            return $this->errorResponse('Erro ao buscar o local.', $e);
        } catch (ModelNotFoundException) {
            return $this->notFoundResponse('Local não encontrado.');
        } catch (\Exception $e) {
            return $this->errorResponse('Erro inesperado ao buscar o local.', $e);
        }
    }

    public function update(PlaceRequest $request, Place $place)
    {
        try {
            return DB::transaction(function () use ($request, $place) {
                $validated = $request->validated();
                $state = $this->getStateBySigla($validated['state']);
                $city = $this->getCityByName($validated['city'], $state);

                $place->update([
                    'name' => $validated['name'],
                    'city_id' => $city->id,
                    'state_id' => $state->id,
                ]);

                return new PlaceResource($place);
            });
        } catch (ModelNotFoundException) {
            return $this->notFoundResponse('Cidade ou estado não encontrados.');
        } catch (QueryException $e) {
            if ($e->getCode() === '23505') {
                return $this->conflictResponse('Já existe um local com este nome para esta cidade e estado.');
            }
            return $this->errorResponse('Erro ao atualizar o local.', $e);
        } catch (\Exception $e) {
            return $this->errorResponse('Erro inesperado ao atualizar o local.', $e);
        }
    }

    public function destroy(Place $place)
    {
        try {
            DB::transaction(function () use ($place) {
                $place->delete();
            });
            return response()->noContent();
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao remover o local.', $e);
        }
    }

    public function search(PlaceSearchRequest $request)
    {
        $name = $request->input('name');

        $perPage = $request->query('per_page', 15);

        $places = Place::with(['city', 'state'])
            ->where('name', 'ILIKE', "%{$name}%")
            ->paginate($perPage);

        return PlaceResource::collection($places);
    }

    private function getCityByName(string $name, State $state): City
    {
        $cityInput = mb_strtolower(trim($name));
        return City::whereRaw('LOWER(nome) = ?', [$cityInput])->where('state_id', $state->id)->firstOrFail();
    }

    private function getStateBySigla(string $sigla): State
    {
        $stateInput = mb_strtoupper(trim($sigla));
        return State::where('sigla', $stateInput)->firstOrFail();
    }

    private function errorResponse(string $message, \Throwable $e)
    {
        return response()->json([
            'message' => $message,
            'error' => $e->getMessage()
        ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
    }

    private function notFoundResponse(string $message)
    {
        return response()->json([
            'message' => $message,
        ], ResponseAlias::HTTP_NOT_FOUND);
    }

    private function conflictResponse(string $message)
    {
        return response()->json([
            'message' => $message,
        ], ResponseAlias::HTTP_CONFLICT);
    }

    private function badRequestResponse(string $message)
    {
        return response()->json([
            'message' => $message,
        ], ResponseAlias::HTTP_BAD_REQUEST);
    }
}
