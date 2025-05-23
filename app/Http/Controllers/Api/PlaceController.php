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
    /**
     * @OA\Get(
     *     path="/api/v1/places",
     *     summary="Listar locais",
     *     tags={"Locais"},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Quantidade de registros por página",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista paginada de locais"
     *     )
     * )
     */
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


    /**
     * @OA\Post(
     *     path="/api/v1/places",
     *     summary="Criar um novo local",
     *     tags={"Locais"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "city", "state"},
     *             @OA\Property(property="name", type="string", example="Praça Central"),
     *             @OA\Property(property="city", type="string", example="São Paulo"),
     *             @OA\Property(property="state", type="string", example="SP")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Local criado com sucesso"),
     *     @OA\Response(response=409, description="Local duplicado"),
     *     @OA\Response(response=500, description="Erro interno")
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/v1/places/{id}",
     *     summary="Buscar um local pelo ID",
     *     tags={"Locais"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID do local",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(response=200, description="Detalhes do local"),
     *     @OA\Response(response=404, description="Local não encontrado"),
     *     @OA\Response(response=400, description="ID inválido")
     * )
     */
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

    /**
     * @OA\Put(
     *     path="/api/v1/places/{id}",
     *     summary="Atualizar um local existente",
     *     tags={"Locais"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID do local",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "city", "state"},
     *             @OA\Property(property="name", type="string", example="Praça Renovada"),
     *             @OA\Property(property="city", type="string", example="Rio de Janeiro"),
     *             @OA\Property(property="state", type="string", example="RJ")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Local atualizado"),
     *     @OA\Response(response=404, description="Local ou cidade/estado não encontrados"),
     *     @OA\Response(response=500, description="Erro interno")
     * )
     */
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

    /**
     * @OA\Delete(
     *     path="/api/v1/places/{id}",
     *     summary="Excluir um local",
     *     tags={"Locais"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID do local",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(response=204, description="Local removido"),
     *     @OA\Response(response=500, description="Erro interno")
     * )
     */
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
