# Laravel AI SDK: Generación de imágenes
Cuarto artículo de una serie de seis sobre Laravel AI SDK. Cubrimos generación de imágenes con OpenAI, Gemini y xAI, control de aspecto y calidad, uso de imágenes de referencia, almacenamiento y generación en background.

La clase `Laravel\Ai\Image` permite generar imágenes usando los providers `openai`, `gemini` o `xai`:
```php
use Laravel\Ai\Image;

$image = Image::of('Un gato durmiendo sobre un teclado')->generate();

$rawContent = (string) $image;
```

## Control de aspecto y calidad

Los métodos `square()`, `portrait()` y `landscape()` controlan la relación de aspecto, mientras que `quality()` define la calidad final de la imagen (`high`, `medium`, `low`):
```php
use Laravel\Ai\Image;

$image = Image::of('Un gato durmiendo sobre un teclado')
    ->quality('high')
    ->landscape()
    ->generate();
```

El método `timeout()` especifica el timeout HTTP en segundos:
```php
$image = Image::of('Un gato durmiendo sobre un teclado')
    ->timeout(120)
    ->generate();
```

## Imágenes de referencia

Podemos adjuntar imágenes de referencia usando el método `attachments()`:
```php
use Laravel\Ai\Files;
use Laravel\Ai\Image;

$image = Image::of('Convertí esta foto en una pintura impresionista.')
    ->attachments([
        Files\Image::fromStorage('photo.jpg'),
        // Files\Image::fromPath('/home/laravel/photo.jpg'),
        // Files\Image::fromUrl('https://example.com/photo.jpg'),
        // $request->file('photo'),
    ])
    ->landscape()
    ->generate();
```

## Almacenamiento

Las imágenes generadas se pueden almacenar en el disco por defecto configurado en `config/filesystems.php`:
```php
$image = Image::of('Un gato durmiendo sobre un teclado')->generate();

$path = $image->store();
$path = $image->storeAs('image.jpg');
$path = $image->storePublicly();
$path = $image->storePubliclyAs('image.jpg');
```

## Generación en background

La generación de imágenes se puede poner en cola:
```php
use Laravel\Ai\Image;
use Laravel\Ai\Responses\ImageResponse;

Image::of('Un gato durmiendo sobre un teclado')
    ->portrait()
    ->queue()
    ->then(function (ImageResponse $image) {
        $path = $image->store();
        // ...
    });
```

## Testing

Para testear generación de imágenes sin hacer llamadas reales al provider, usamos el método `fake()`:
```php
use Laravel\Ai\Image;
use Laravel\Ai\Prompts\ImagePrompt;

// Respuesta fija para todos los prompts
Image::fake();

// Lista de respuestas en orden
Image::fake([
    base64_encode($firstImage),
    base64_encode($secondImage),
]);

// Respuesta dinámica basada en el prompt
Image::fake(function (ImagePrompt $prompt) {
    return base64_encode('...');
});
```

Aserciones disponibles:
```php
Image::assertGenerated(function (ImagePrompt $prompt) {
    return $prompt->contains('sunset') && $prompt->isLandscape();
});

Image::assertNotGenerated('Missing prompt');

Image::assertNothingGenerated();
```

Para generación en cola:
```php
use Laravel\Ai\Prompts\QueuedImagePrompt;

Image::assertQueued(
    fn (QueuedImagePrompt $prompt) => $prompt->contains('sunset')
);

Image::assertNotQueued('Missing prompt');

Image::assertNothingQueued();
```

Para evitar generaciones sin fake definido, usamos `preventStrayImages()`:
```php
Image::fake()->preventStrayImages();
```

**Próximo artículo:** Laravel AI SDK: Audio TTS y STT