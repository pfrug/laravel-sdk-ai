# Laravel AI SDK: Embeddings y Vector Stores
Sexto y último artículo de una serie de seis sobre Laravel AI SDK. Cubrimos generación de embeddings, caché, búsqueda vectorial en PostgreSQL con pgvector, reranking de documentos y vector stores para RAG

## Generación de embeddings

Laravel incluye el método `toEmbeddings()` en la clase `Stringable` para generar embeddings vectoriales:
```php
use Illuminate\Support\Str;

$embeddings = Str::of('Mallorca tiene hermosos senderos.')->toEmbeddings();
```

Para generar embeddings de múltiples inputs a la vez, usamos la clase `Embeddings`:
```php
use Laravel\Ai\Embeddings;

$response = Embeddings::for([
    'Mallorca tiene hermosos senderos.',
    'Laravel es un framework PHP.',
])->generate();

$response->embeddings; // [[0.123, 0.456, ...], [0.789, 0.012, ...]]
```

Podemos especificar las dimensiones y el provider:
```php
$response = Embeddings::for(['Mallorca tiene hermosos senderos.'])
    ->dimensions(1536)
    ->generate(Lab::OpenAI, 'text-embedding-3-small');
```

## Caché de embeddings

La generación de embeddings se puede cachear para evitar llamadas redundantes al API. Para habilitarlo globalmente, configuramos `ai.caching.embeddings.cache` en `true`:
```php
'caching' => [
    'embeddings' => [
        'cache' => true,
        'store' => env('CACHE_STORE', 'database'),
        // ...
    ],
],
```

Cuando está habilitado, los embeddings se cachean por 30 días. La cache key se basa en el provider, modelo, dimensiones y contenido de entrada.

También podemos habilitar caché para una request específica:
```php
$response = Embeddings::for(['Mallorca tiene hermosos senderos.'])
    ->cache()
    ->generate();
```

Con duración customizada:
```php
$response = Embeddings::for(['Mallorca tiene hermosos senderos.'])
    ->cache(seconds: 3600) // Cache por 1 hora
    ->generate();
```

El método `toEmbeddings()` también acepta un parámetro `cache`:
```php
// Cache con duración por defecto
$embeddings = Str::of('Mallorca tiene hermosos senderos.')->toEmbeddings(cache: true);

// Cache con duración específica
$embeddings = Str::of('Mallorca tiene hermosos senderos.')->toEmbeddings(cache: 3600);
```

## Búsqueda vectorial en PostgreSQL

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

## Reranking

El reranking permite reordenar una lista de documentos según su relevancia a una query usando comprensión semántica:
```php
use Laravel\Ai\Reranking;

$response = Reranking::of([
    'Django es un framework web de Python.',
    'Laravel es un framework de aplicaciones web en PHP.',
    'React es una biblioteca JavaScript para construir interfaces de usuario.',
])->rerank('frameworks PHP');

// Acceder al primer resultado
$response->first()->document; // "Laravel es un framework de aplicaciones web en PHP."
$response->first()->score;    // 0.95
$response->first()->index;    // 1 (posición original)
```

El método `limit()` restringe el número de resultados retornados:
```php
$response = Reranking::of($documents)
    ->limit(5)
    ->rerank('search query');
```

### Reranking de colecciones

Las colecciones de Laravel incluyen un macro `rerank()`. El primer argumento especifica qué campo(s) usar para reranking, el segundo es la query:
```php
// Rerank por un solo campo
$posts = Post::all()
    ->rerank('body', 'tutoriales Laravel');

// Rerank por múltiples campos (enviados como JSON)
$reranked = $posts->rerank(['title', 'body'], 'tutoriales Laravel');

// Rerank usando un closure para construir el documento
$reranked = $posts->rerank(
    fn ($post) => $post->title.': '.$post->body,
    'tutoriales Laravel'
);
```

También podemos limitar los resultados y especificar un provider:
```php
$reranked = $posts->rerank(
    by: 'content',
    query: 'tutoriales Laravel',
    limit: 10,
    provider: Lab::Cohere
);
```

## Vector Stores

Los vector stores permiten crear colecciones de archivos indexados para retrieval-augmented generation (RAG). La clase `Laravel\Ai\Stores` proporciona métodos para crear, recuperar y eliminar vector stores:
```php
use Laravel\Ai\Stores;

// Crear un nuevo vector store
$store = Stores::create('Base de Conocimiento');

// Crear con opciones adicionales
$store = Stores::create(
    name: 'Base de Conocimiento',
    description: 'Documentación y materiales de referencia.',
    expiresWhenIdleFor: days(30),
);

return $store->id;
```

Para recuperar un vector store existente por su ID:
```php
use Laravel\Ai\Stores;

$store = Stores::get('store_id');

$store->id;
$store->name;
$store->fileCounts;
$store->ready;
```

Para eliminar un vector store:
```php
use Laravel\Ai\Stores;

// Eliminar por ID
Stores::delete('store_id');

// O eliminar via instancia
$store = Stores::get('store_id');
$store->delete();
```

### Agregar archivos a stores

Una vez que tenemos un vector store, podemos agregar archivos usando el método `add()`. Los archivos agregados se indexan automáticamente para búsqueda semántica usando la provider tool FileSearch:
```php
use Laravel\Ai\Files\Document;
use Laravel\Ai\Stores;

$store = Stores::get('store_id');

// Agregar un archivo ya almacenado con el provider
$document = $store->add('file_id');
$document = $store->add(Document::fromId('file_id'));

// O almacenar y agregar un archivo en un solo paso
$document = $store->add(Document::fromPath('/path/to/document.pdf'));
$document = $store->add(Document::fromStorage('manual.pdf'));
$document = $store->add($request->file('document'));

$document->id;
$document->fileId;
```

Podemos adjuntar metadata a los archivos al agregarlos. Esta metadata se puede usar luego para filtrar resultados de búsqueda con la provider tool FileSearch:
```php
$store->add(Document::fromPath('/path/to/document.pdf'), metadata: [
    'author' => 'Taylor Otwell',
    'department' => 'Engineering',
    'year' => 2026,
]);
```

Para remover un archivo de un store:
```php
$store->remove('file_id');
```

Remover un archivo del vector store no lo elimina del almacenamiento de archivos del provider. Para remover del store y eliminarlo permanentemente:
```php
$store->remove('file_abc123', deleteFile: true);
```

## Testing

### Testing de embeddings

Para testear generación de embeddings sin hacer llamadas reales al provider:
```php
use Laravel\Ai\Embeddings;
use Laravel\Ai\Prompts\EmbeddingsPrompt;

// Generar embeddings fake automáticamente con las dimensiones apropiadas
Embeddings::fake();

// Proveer una lista de respuestas
Embeddings::fake([
    [$firstEmbeddingVector],
    [$secondEmbeddingVector],
]);

// Manejar respuestas dinámicamente
Embeddings::fake(function (EmbeddingsPrompt $prompt) {
    return array_map(
        fn () => Embeddings::fakeEmbedding($prompt->dimensions),
        $prompt->inputs
    );
});
```

Aserciones disponibles:
```php
Embeddings::assertGenerated(function (EmbeddingsPrompt $prompt) {
    return $prompt->contains('Laravel') && $prompt->dimensions === 1536;
});

Embeddings::assertNotGenerated(
    fn (EmbeddingsPrompt $prompt) => $prompt->contains('Other')
);

Embeddings::assertNothingGenerated();
```

Para generación en cola:
```php
use Laravel\Ai\Prompts\QueuedEmbeddingsPrompt;

Embeddings::assertQueued(
    fn (QueuedEmbeddingsPrompt $prompt) => $prompt->contains('Laravel')
);

Embeddings::assertNotQueued(
    fn (QueuedEmbeddingsPrompt $prompt) => $prompt->contains('Other')
);

Embeddings::assertNothingQueued();
```

Para evitar generaciones sin fake definido:
```php
Embeddings::fake()->preventStrayEmbeddings();
```

### Testing de reranking

Para testear operaciones de reranking sin hacer llamadas reales al provider:
```php
use Laravel\Ai\Reranking;
use Laravel\Ai\Responses\Data\RankedDocument;

// Generar respuestas fake automáticamente
Reranking::fake();

// Proveer respuestas customizadas
Reranking::fake([
    [
        new RankedDocument(index: 0, document: 'First', score: 0.95),
        new RankedDocument(index: 1, document: 'Second', score: 0.80),
    ],
]);
```

Aserciones disponibles:
```php
use Laravel\Ai\Prompts\RerankingPrompt;

Reranking::assertReranked(function (RerankingPrompt $prompt) {
    return $prompt->contains('Laravel') && $prompt->limit === 5;
});

Reranking::assertNotReranked(
    fn (RerankingPrompt $prompt) => $prompt->contains('Django')
);

Reranking::assertNothingReranked();
```

### Testing de vector stores

Para testear operaciones de vector stores sin hacer llamadas reales al provider:
```php
use Laravel\Ai\Stores;

Stores::fake();
```

Aserciones disponibles para stores creados o eliminados:
```php
use Laravel\Ai\Stores;

// Crear store
$store = Stores::create('Base de Conocimiento');

// Hacer aserciones
Stores::assertCreated('Base de Conocimiento');

Stores::assertCreated(fn (string $name, ?string $description) =>
    $name === 'Base de Conocimiento'
);

Stores::assertNotCreated('Otro Store');

Stores::assertNothingCreated();
```

Para aserciones de eliminación:
```php
Stores::assertDeleted('store_id');
Stores::assertNotDeleted('other_store_id');
Stores::assertNothingDeleted();
```

Para aserciones de archivos agregados o removidos de un store:
```php
Stores::fake();

$store = Stores::get('store_id');

// Agregar / remover archivos
$store->add('added_id');
$store->remove('removed_id');

// Hacer aserciones
$store->assertAdded('added_id');
$store->assertRemoved('removed_id');

$store->assertNotAdded('other_file_id');
$store->assertNotRemoved('other_file_id');
```

Si un archivo se almacena en el provider y se agrega a un vector store en la misma request, podemos no conocer el ID del provider. En ese caso, pasamos un closure a `assertAdded()`:
```php
use Laravel\Ai\Contracts\Files\StorableFile;
use Laravel\Ai\Files\Document;

$store->add(Document::fromString('Hello, World!', 'text/plain')->as('hello.txt'));

$store->assertAdded(fn (StorableFile $file) => $file->name() === 'hello.txt');
$store->assertAdded(fn (StorableFile $file) => $file->content() === 'Hello, World!');
```