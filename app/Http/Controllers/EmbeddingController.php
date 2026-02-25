<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmbeddingRequest;
use App\Http\Requests\RerankRequest;
use App\Http\Requests\VectorSearchRequest;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Reranking;

class EmbeddingController extends Controller
{
    public function embeddings(EmbeddingRequest $request): JsonResponse
    {
        $response = Embeddings::for($request->validated('input'))->generate();

        return response()->json([
            'embeddings' => $response->embeddings,
            'usage' => $response->usage,
        ]);
    }

    public function search(VectorSearchRequest $request): JsonResponse
    {
        $documents = Document::query()
            ->whereVectorSimilarTo('embedding', $request->validated('query'))
            ->limit($request->validated('limit', 5))
            ->get(['id', 'title', 'content']);

        return response()->json([
            'results' => $documents,
        ]);
    }

    public function rerank(RerankRequest $request): JsonResponse
    {
        $response = Reranking::of($request->validated('documents'))
            ->rerank($request->validated('query'));

        return response()->json([
            'results' => $response->map(fn ($item) => [
                'document' => $item->document,
                'score' => $item->score,
                'index' => $item->index,
            ])->all(),
        ]);
    }
}
