<?php

namespace App\Ai\Agents;

use App\Ai\Middleware\LogPrompts;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasMiddleware;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider(Lab::Anthropic)]
#[Temperature(0.7)]
class SalesCoach implements Agent, Conversational, HasMiddleware
{
    use Promptable, RemembersConversations;

    public function instructions(): Stringable|string
    {
        return 'You are a sales coach. You analyze sales call transcripts and provide actionable feedback.';
    }

    public function middleware(): array
    {
        return [
            new LogPrompts,
        ];
    }
}
