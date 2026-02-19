<?php

namespace App\Http\Controllers;

/**
 * Handles real-time streaming responses from AI providers.
 *
 * Responsibilities:
 * - Delivering token-by-token responses via Server-Sent Events (SSE).
 * - Broadcasting streamed content through Laravel's event system.
 * - Managing long-lived connections and graceful stream termination.
 */
class StreamingController extends Controller
{
    //
}
