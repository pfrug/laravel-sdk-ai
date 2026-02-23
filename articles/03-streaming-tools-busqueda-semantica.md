# Laravel AI SDK — Streaming, Tools y búsqueda semántica

## Streaming

El método `stream()` permite recibir la respuesta del agente de forma progresiva en lugar de esperar a que termine completamente. Devuelve un `StreamableAgentResponse` que puede retornarse directamente desde una ruta para enviar Server-Sent Events (SSE) al cliente:
```php
use App\Ai\Agents\LaravelMentor;

Route::get('/mentor', function () {
    return (new LaravelMentor)->stream('Listá brevemente las novedades de Laravel 12.');
});
```

El método `then()` nos permite ejecutar lógica cuando el streaming termina:
```php
use Laravel\Ai\Responses\StreamedAgentResponse;

Route::get('/mentor', function () {
    return (new LaravelMentor)
        ->stream('Listá brevemente las novedades de Laravel 12.')
        ->then(function (StreamedAgentResponse $response) {
            // Acceso a $response->text, $response->events, $response->usage
        });
});
```

También podemos iterar manualmente sobre los eventos:
```php
$stream = (new LaravelMentor)->stream('Listá brevemente las novedades de Laravel 12.');

foreach ($stream as $event) {
    // Procesar cada evento
}
```

### Protocolo Vercel AI SDK

Para usar el protocolo de Vercel AI SDK en lugar de SSE estándar, invocamos `usingVercelDataProtocol()`:
```php
Route::get('/mentor', function () {
    return (new LaravelMentor)
        ->stream('Listá brevemente las novedades de Laravel 12.')
        ->usingVercelDataProtocol();
});
```

## Broadcasting

Podemos broadcast los eventos de streaming directamente desde cada evento individual:
```php
use Illuminate\Broadcasting\Channel;

$stream = (new LaravelMentor)->stream('Listá brevemente las novedades de Laravel 12.');

foreach ($stream as $event) {
    $event->broadcast(new Channel('channel-name'));
}
```

O encolar el agente completo para que ejecute y haga broadcast automáticamente:
```php
(new LaravelMentor)->broadcastOnQueue(
    'Listá brevemente las novedades de Laravel 12.',
    new Channel('channel-name'),
);
```

## Queueing

El método `queue()` permite procesar la respuesta en background. Los métodos `then()` y `catch()` registran closures que se ejecutan cuando hay una respuesta o un error:
```php
use Illuminate\Http\Request;
use Laravel\Ai\Responses\AgentResponse;
use Throwable;

Route::post('/mentor', function (Request $request) {
    (new LaravelMentor)
        ->queue($request->input('prompt'))
        ->then(function (AgentResponse $response) {
            // Procesar respuesta
        })
        ->catch(function (Throwable $e) {
            // Manejar error
        });

    return back();
});
```

## Tools

Las tools permiten que los agentes ejecuten funcionalidad adicional durante el procesamiento.
Se puede crear con el siguiente comoando Artisan:
```bash
php artisan make:tool RandomNumberGenerator
```

Esto genera una clase en `app/Ai/Tools` con tres métodos: `description()`, `handle()` y `schema()`:
```php
<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class RandomNumberGenerator implements Tool
{
    public function description(): Stringable|string
    {
        return 'Generates a cryptographically secure random number within a given range.';
    }

    public function handle(Request $request): Stringable|string
    {
        return (string) random_int($request['min'], $request['max']);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'min' => $schema->integer()->min(0)->required(),
            'max' => $schema->integer()->required(),
        ];
    }
}
```

Para que un agente tenga acceso a tools, implementamos `HasTools` y definimos el método `tools()`:
```php
use App\Ai\Tools\RandomNumberGenerator;
use Laravel\Ai\Contracts\HasTools;

class Calculator implements Agent, HasTools
{
    use Promptable;

    public function tools(): iterable
    {
        return [
            new RandomNumberGenerator,
        ];
    }
}
```

### Similarity Search

La tool `SimilaritySearch` permite que los agentes busquen documentos similares usando embeddings vectoriales almacenados en la base de datos. Es útil para RAG (Retrieval-Augmented Generation).

La forma más simple es usar `usingModel()` con un modelo Eloquent que tenga embeddings vectoriales:
```php
use App\Models\Document;
use Laravel\Ai\Tools\SimilaritySearch;

public function tools(): iterable
{
    return [
        SimilaritySearch::usingModel(Document::class, 'embedding'),
    ];
}
```

El primer argumento es la clase del modelo, el segundo es la columna que contiene los vectores.

Podemos especificar umbral de similitud mínima, límite de resultados y customizar la query:
```php
SimilaritySearch::usingModel(
    model: Document::class,
    column: 'embedding',
    minSimilarity: 0.7,
    limit: 10,
    query: fn ($query) => $query->where('published', true),
),
```

Para mayor control, creamos una similarity search con un closure customizado:
```php
use App\Models\Document;
use Laravel\Ai\Tools\SimilaritySearch;

public function tools(): iterable
{
    return [
        new SimilaritySearch(using: function (string $query) {
            return Document::query()
                ->where('user_id', $this->user->id)
                ->whereVectorSimilarTo('embedding', $query)
                ->limit(10)
                ->get();
        }),
    ];
}
```

Podemos personalizar la descripción de la tool:
```php
SimilaritySearch::usingModel(Document::class, 'embedding')
    ->withDescription('Buscar en la base de conocimiento artículos relevantes.'),
```

## Provider Tools

Las provider tools son herramientas implementadas nativamente por los AI providers, ejecutadas por el provider mismo en lugar de nuestra aplicación.

### Web Search

Permite que los agentes busquen información en tiempo real en la web. Soportado por Anthropic, OpenAI y Gemini:
```php
use Laravel\Ai\Providers\Tools\WebSearch;

public function tools(): iterable
{
    return [
        new WebSearch,
    ];
}
```

Podemos limitar el número de búsquedas o restringir dominios:
```php
(new WebSearch)->max(5)->allow(['laravel.com', 'php.net']),
```

Para refinar resultados por ubicación:
```php
(new WebSearch)->location(
    city: 'New York',
    region: 'NY',
    country: 'US'
);
```

### Web Fetch

Permite que los agentes lean el contenido de páginas web. Soportado por Anthropic y Gemini:
```php
use Laravel\Ai\Providers\Tools\WebFetch;

public function tools(): iterable
{
    return [
        new WebFetch,
    ];
}
```

Podemos limitar fetches o restringir dominios:
```php
(new WebFetch)->max(3)->allow(['docs.laravel.com']),
```

### File Search

Permite que los agentes busquen en archivos almacenados en vector stores. Soportado por OpenAI y Gemini:
```php
use Laravel\Ai\Providers\Tools\FileSearch;

public function tools(): iterable
{
    return [
        new FileSearch(stores: ['store_id']),
    ];
}
```

Podemos buscar en múltiples stores:
```php
new FileSearch(stores: ['store_1', 'store_2']);
```

Si los archivos tienen metadata, podemos filtrar los resultados. Para filtros simples de igualdad:
```php
new FileSearch(stores: ['store_id'], where: [
    'author' => 'Taylor Otwell',
    'year' => 2026,
]);
```

Para filtros más complejos, usamos un closure:
```php
use Laravel\Ai\Providers\Tools\FileSearchQuery;

new FileSearch(stores: ['store_id'], where: fn (FileSearchQuery $query) =>
    $query->where('author', 'Taylor Otwell')
        ->whereNot('status', 'draft')
        ->whereIn('category', ['news', 'updates'])
);
```

## Búsqueda semántica con vectores

Laravel incluye soporte nativo para columnas vectoriales en PostgreSQL mediante la extensión `pgvector`.

En la migración, definimos una columna vector especificando las dimensiones:
```php
Schema::ensureVectorExtensionExists();

Schema::create('documents', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('content');
    $table->vector('embedding', dimensions: 1536);
    $table->timestamps();
});
```

Podemos agregar un índice vectorial para acelerar las búsquedas. Laravel crea automáticamente un índice HNSW con distancia coseno:
```php
$table->vector('embedding', dimensions: 1536)->index();
```

En el modelo Eloquent, casteamos la columna a array:
```php
protected function casts(): array
{
    return [
        'embedding' => 'array',
    ];
}
```

Para buscar registros similares, usamos `whereVectorSimilarTo()`. Este método filtra por similitud coseno mínima (entre 0.0 y 1.0, donde 1.0 es idéntico) y ordena por similitud:
```php
use App\Models\Document;

$documents = Document::query()
    ->whereVectorSimilarTo('embedding', $queryEmbedding, minSimilarity: 0.4)
    ->limit(10)
    ->get();
```

El parámetro `$queryEmbedding` puede ser un array de floats o un string. Cuando es string, Laravel genera los embeddings automáticamente:
```php
$documents = Document::query()
    ->whereVectorSimilarTo('embedding', 'mejores rutas de senderismo en Mallorca')
    ->limit(10)
    ->get();
```

Para mayor control, podemos usar los métodos de bajo nivel `whereVectorDistanceLessThan`, `selectVectorDistance` y `orderByVectorDistance` de forma independiente:
```php
$documents = Document::query()
    ->select('*')
    ->selectVectorDistance('embedding', $queryEmbedding, as: 'distance')
    ->whereVectorDistanceLessThan('embedding', $queryEmbedding, maxDistance: 0.3)
    ->orderByVectorDistance('embedding', $queryEmbedding)
    ->limit(10)
    ->get();
```

**Próximo artículo:** Laravel AI SDK — Generación de imágenes