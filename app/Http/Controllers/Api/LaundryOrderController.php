<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LaundryOrderRequest;
use App\Http\Resources\LaundryOrderResource;
use App\Models\LaundryOrder;
use App\Services\LaundryOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LaundryOrderController extends Controller
{
    public function __construct(private readonly LaundryOrderService $orders) {}

    public function index(Request $request): JsonResponse
    {
        $query = LaundryOrder::with(['customer', 'serviceType', 'payments'])->when(! $request->user()->isStaff(), fn ($builder) => $builder->where('customer_id', $request->user()->id))->when($request->filled('status'), fn ($builder) => $builder->where('status', $request->string('status')))->when($request->filled('search'), fn ($builder) => $builder->where('tracking_number', 'like', '%'.$request->string('search').'%'))->latest();

        return response()->json(LaundryOrderResource::collection($query->paginate(min($request->integer('per_page', 15), 100)))->response()->getData(true));
    }

    public function store(LaundryOrderRequest $request): JsonResponse
    {
        return response()->json(['data' => new LaundryOrderResource($this->orders->create($request->user(), $request->validated()))], 201);
    }

    public function show(LaundryOrder $laundryOrder): LaundryOrderResource
    {
        Gate::authorize('view', $laundryOrder);

        return new LaundryOrderResource($laundryOrder->load(['customer', 'serviceType', 'payments', 'statusEvents']));
    }

    public function transition(Request $request, LaundryOrder $laundryOrder): LaundryOrderResource
    {
        $data = $request->validate(['status' => ['required', 'in:washing,drying,ready,claimed,cancelled']]);

        return new LaundryOrderResource($this->orders->transition($laundryOrder, $request->user(), $data['status']));
    }

    public function pay(Request $request, LaundryOrder $laundryOrder): LaundryOrderResource
    {
        $data = $request->validate(['amount' => ['required', 'numeric', 'gt:0'], 'method' => ['required', 'in:cash,card,e_wallet'], 'reference' => ['nullable', 'string', 'max:100', 'unique:payments,reference']]);

        return new LaundryOrderResource($this->orders->pay($laundryOrder, $request->user(), $data));
    }
}
