<?php

namespace App\Http\Controllers;

use App\Ai\Agents\Calculator;
use App\Ai\Agents\CodeReviewer;
use App\Ai\Agents\LaravelMentor;
use App\Http\Requests\ConversationRequest;
use App\Http\Requests\PromptRequest;
use App\Http\Requests\StructuredRequest;
use App\Http\Requests\ToolRequest;
use Illuminate\Http\JsonResponse;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\StreamableAgentResponse;
use Throwable;

use function Laravel\Ai\agent;

class AgentController extends Controller
{
    public function prompt(PromptRequest $request): JsonResponse
    {
        $response = agent(
            instructions: $request->validated('instructions', ''),
        )->prompt(
            prompt: $request->validated('prompt'),
            provider: $request->provider(),
            model: $request->validated('model'),
        );

        return response()->json([
            'text' => $response->text,
            'usage' => $response->usage,
        ]);
    }

    public function conversation(ConversationRequest $request): JsonResponse
    {
        $agent = LaravelMentor::make(user: $request->user());
        $conversationId = $request->validated('conversation_id');

        if ($conversationId) {
            $agent = $agent->continue($conversationId, as: $request->user());
        } else {
            $agent = $agent->forUser($request->user());
        }

        $response = $agent->prompt($request->validated('prompt'));

        return response()->json([
            'text' => $response->text,
            'conversation_id' => $agent->currentConversation(),
            'usage' => $response->usage,
        ]);
    }

    public function structured(StructuredRequest $request): JsonResponse
    {
        $response = (new CodeReviewer)->prompt($request->validated('prompt'));

        return response()->json([
            'data' => $response->structured,
            'usage' => $response->usage,
        ]);
    }

    public function stream(ToolRequest $request): StreamableAgentResponse
    {
        return (new LaravelMentor)->stream($request->validated('prompt'));
    }

    public function tools(ToolRequest $request): JsonResponse
    {
        $response = (new Calculator)->prompt($request->validated('prompt'));

        return response()->json([
            'text' => $response->text,
            'usage' => $response->usage,
        ]);
    }

    /**
     * Requires running `php artisan queue:work` and checking logs for the result.
     */
    public function queue(ToolRequest $request): JsonResponse
    {
        (new LaravelMentor)
            ->queue($request->validated('prompt'))
            ->then(function (AgentResponse $response) {
                logger('Queued response: '.$response->text);
            })
            ->catch(function (Throwable $e) {
                logger('Queued error: '.$e->getMessage());
            });

        return response()->json(['status' => 'queued'], 202);
    }
}
