<?php

namespace App\Http\Controllers;

use App\Ai\Agents\LaravelMentor;
use App\Http\Requests\ToolRequest;
use Laravel\Ai\Responses\StreamableAgentResponse;

class StreamingController extends Controller
{
    public function __invoke(ToolRequest $request): StreamableAgentResponse
    {
        return (new LaravelMentor)->stream($request->validated('prompt'));
    }
}
