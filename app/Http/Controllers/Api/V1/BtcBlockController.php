<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class BtcBlockController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'blocks' => [],
            ],
        ]);
    }
}
