<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(ServiceType::when(! $request->user()->isStaff(), fn ($query) => $query->where('active', true))->orderBy('name')->paginate(min($request->integer('per_page', 15), 100)));
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['data' => ServiceType::create($this->validated($request))], 201);
    }

    public function update(Request $request, ServiceType $serviceType): JsonResponse
    {
        $serviceType->update($this->validated($request, $serviceType));

        return response()->json(['data' => $serviceType]);
    }

    private function validated(Request $request, ?ServiceType $serviceType = null): array
    {
        return $request->validate(['name' => ['required', 'string', 'max:120', Rule::unique('service_types')->ignore($serviceType)], 'unit' => ['required', 'in:kg,item'], 'price_per_unit' => ['required', 'numeric', 'min:0'], 'turnaround_hours' => ['required', 'integer', 'min:1', 'max:720'], 'active' => ['sometimes', 'boolean']]);
    }
}
