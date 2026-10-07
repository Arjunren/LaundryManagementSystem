<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LaundryOrder;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json(['data' => ['active_orders' => LaundryOrder::whereNotIn('status', ['claimed', 'cancelled'])->count(), 'ready_for_claim' => LaundryOrder::where('status', 'ready')->count(), 'overdue_orders' => LaundryOrder::whereNotIn('status', ['claimed', 'cancelled'])->where('due_at', '<', now())->count(), 'today_revenue' => Payment::whereDate('paid_at', today())->sum('amount')]]);
    }
}
