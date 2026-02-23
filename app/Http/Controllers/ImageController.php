<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImageRequest;
use Illuminate\Http\JsonResponse;
use Laravel\Ai\Image;
use Laravel\Ai\Responses\ImageResponse;

class ImageController extends Controller
{
    public function generate(ImageRequest $request): JsonResponse
    {
        $image = Image::of($request->validated('prompt'));

        if ($format = $request->validated('format')) {
            $image->{$format}();
        }

        if ($quality = $request->validated('quality')) {
            $image->quality($quality);
        }

        $response = $image->generate();
        $path = $response->store();

        return response()->json(['path' => $path]);
    }

    /**
     * Requires running `php artisan queue:work` and checking logs for the result.
     */
    public function queue(ImageRequest $request): JsonResponse
    {
        $image = Image::of($request->validated('prompt'));

        if ($format = $request->validated('format')) {
            $image->{$format}();
        }

        if ($quality = $request->validated('quality')) {
            $image->quality($quality);
        }

        $image->queue()
            ->then(function (ImageResponse $response) {
                $path = $response->store();
                logger('Image stored: '.$path);
            });

        return response()->json(['status' => 'queued'], 202);
    }
}
