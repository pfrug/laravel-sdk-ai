<?php

namespace App\Http\Controllers;

use App\Http\Requests\SttRequest;
use App\Http\Requests\TtsRequest;
use Illuminate\Http\JsonResponse;
use Laravel\Ai\Audio;
use Laravel\Ai\Transcription;

class AudioController extends Controller
{
    public function tts(TtsRequest $request): JsonResponse
    {
        $audio = Audio::of($request->validated('text'));

        if ($voice = $request->validated('voice')) {
            $audio->{$voice}();
        }

        $response = $audio->generate();
        $path = $response->store();

        return response()->json(['path' => $path]);
    }

    public function stt(SttRequest $request): JsonResponse
    {
        $transcript = Transcription::fromUpload($request->file('audio'))->generate();

        return response()->json(['text' => (string) $transcript]);
    }
}
