<?php

namespace App\Ai\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;

class LogPrompts
{
    public function handle(AgentPrompt $prompt, Closure $next)
    {
        Log::info('AI prompt sent', [
            'agent' => $prompt->agent::class,
            'prompt' => $prompt->prompt,
        ]);

        return $next($prompt)->then(function (AgentResponse $response) {
            Log::info('AI response received', [
                'text' => $response->text,
            ]);
        });
    }
}
