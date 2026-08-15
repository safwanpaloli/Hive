<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Analytics\SocialAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function social(Request $request, SocialAnalyticsService $service): JsonResponse
    {
        $data = $service->forUser($request->user(), $request->boolean('refresh'));

        return response()->json($data);
    }
}
