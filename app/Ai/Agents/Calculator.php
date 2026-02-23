<?php

namespace App\Ai\Agents;

use App\Ai\Tools\RandomNumberGenerator;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\WebSearch;
use Stringable;

#[Provider(Lab::Anthropic)]
#[Temperature(0.3)]
class Calculator implements Agent, HasTools
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You are a calculator assistant. You can generate random numbers and search the web for information.';
    }

    public function tools(): iterable
    {
        return [
            new RandomNumberGenerator,
            new WebSearch,
        ];
    }
}
