<?php

namespace App\Http\Controllers;

use App\Ai\Agents\SalesAnalyzer;
use App\Ai\Agents\SalesCoach;
use App\Http\Requests\ConversationRequest;
use App\Http\Requests\PromptRequest;
use App\Http\Requests\StructuredRequest;
use Illuminate\Http\JsonResponse;

use function Laravel\Ai\agent;

/**
 * Handles AI agent interactions — prompts, multi-turn conversations, and structured output.
 */
class AgentController extends Controller
{
    /**
     * Send a prompt to an AI provider and return the response.
     */
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

    /**
     * Start or continue a multi-turn conversation using the SalesCoach agent.
     */
    public function conversation(ConversationRequest $request): JsonResponse
    {
        $agent = SalesCoach::make(user: $request->user());
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

    /**
     * Send a prompt and return schema-constrained structured output.
     */
    public function structured(StructuredRequest $request): JsonResponse
    {
        $response = (new SalesAnalyzer)->prompt($request->validated('prompt'));

        return response()->json([
            'data' => $response->structured,
            'usage' => $response->usage,
        ]);
    }
}
