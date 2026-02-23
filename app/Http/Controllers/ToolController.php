<?php

namespace App\Http\Controllers;

use App\Ai\Agents\Calculator;
use App\Http\Requests\ToolRequest;
use Illuminate\Http\JsonResponse;

class ToolController extends Controller
{
    public function __invoke(ToolRequest $request): JsonResponse
    {
        $response = (new Calculator)->prompt($request->validated('prompt'));

        return response()->json([
            'text' => $response->text,
            'usage' => $response->usage,
        ]);
    }
}
