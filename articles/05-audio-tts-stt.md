# Laravel AI SDK: Audio TTS y STT
Quinto artículo de una serie de 6 sobre Laravel AI SDK. Cubrimos Text-to-Speech con selección de voces y generación de audio, Speech-to-Text con transcripción y diarización, almacenamiento y procesamiento en background

## Text-to-Speech (TTS)

La clase `Laravel\Ai\Audio` permite generar audio a partir de texto:
```php
use Laravel\Ai\Audio;

$audio = Audio::of('Me encanta programar con Laravel.')->generate();

$rawContent = (string) $audio;
```

## Selección de voz

Los métodos `male()`, `female()` y `voice()` determinan la voz del audio generado:
```php
$audio = Audio::of('Me encanta programar con Laravel.')
    ->female()
    ->generate();

$audio = Audio::of('Me encanta programar con Laravel.')
    ->voice('voice-id-or-name')
    ->generate();
```

El método `instructions()` permite guiar dinámicamente cómo debe sonar el audio:
```php
$audio = Audio::of('Me encanta programar con Laravel.')
    ->female()
    ->instructions('Dicho como un pirata')
    ->generate();
```

## Almacenamiento

El audio generado se puede almacenar en el disco por defecto configurado en `config/filesystems.php`:
```php
$audio = Audio::of('Me encanta programar con Laravel.')->generate();

$path = $audio->store();
$path = $audio->storeAs('audio.mp3');
$path = $audio->storePublicly();
$path = $audio->storePubliclyAs('audio.mp3');
```

## Generación en background

La generación de audio se puede encolar:
```php
use Laravel\Ai\Audio;
use Laravel\Ai\Responses\AudioResponse;

Audio::of('Me encanta programar con Laravel.')
    ->queue()
    ->then(function (AudioResponse $audio) {
        $path = $audio->store();
        // ...
    });
```

## Speech-to-Text (STT)

La clase `Laravel\Ai\Transcription` genera transcripciones de archivos de audio:
```php
use Laravel\Ai\Transcription;

$transcript = Transcription::fromPath('/home/laravel/audio.mp3')->generate();
$transcript = Transcription::fromStorage('audio.mp3')->generate();
$transcript = Transcription::fromUpload($request->file('audio'))->generate();

return (string) $transcript;
```

## Diarización

El método `diarize()` permite obtener la transcripción segmentada por hablante además del texto plano:
```php
$transcript = Transcription::fromStorage('audio.mp3')
    ->diarize()
    ->generate();
```

## Transcripción en background

La transcripción se puede encolar:
```php
use Laravel\Ai\Transcription;
use Laravel\Ai\Responses\TranscriptionResponse;

Transcription::fromStorage('audio.mp3')
    ->queue()
    ->then(function (TranscriptionResponse $transcript) {
        // ...
    });
```

## Testing TTS

Para testear generación de audio sin hacer llamadas reales al provider:
```php
use Laravel\Ai\Audio;
use Laravel\Ai\Prompts\AudioPrompt;

// Respuesta fija para todos los prompts
Audio::fake();

// Lista de respuestas en orden
Audio::fake([
    base64_encode($firstAudio),
    base64_encode($secondAudio),
]);

// Respuesta dinámica basada en el prompt
Audio::fake(function (AudioPrompt $prompt) {
    return base64_encode('...');
});
```

Aserciones disponibles:
```php
Audio::assertGenerated(function (AudioPrompt $prompt) {
    return $prompt->contains('Hello') && $prompt->isFemale();
});

Audio::assertNotGenerated('Missing prompt');

Audio::assertNothingGenerated();
```

Para generación encolada:
```php
use Laravel\Ai\Prompts\QueuedAudioPrompt;

Audio::assertQueued(
    fn (QueuedAudioPrompt $prompt) => $prompt->contains('Hello')
);

Audio::assertNotQueued('Missing prompt');

Audio::assertNothingQueued();
```

Para evitar generaciones sin fake definido:
```php
Audio::fake()->preventStrayAudio();
```

## Testing STT

Para testear transcripciones sin hacer llamadas reales al provider:
```php
use Laravel\Ai\Transcription;
use Laravel\Ai\Prompts\TranscriptionPrompt;

// Respuesta fija para todos los prompts
Transcription::fake();

// Lista de respuestas en orden
Transcription::fake([
    'Primera transcripción.',
    'Segunda transcripción.',
]);

// Respuesta dinámica basada en el prompt
Transcription::fake(function (TranscriptionPrompt $prompt) {
    return 'Texto transcrito...';
});
```

Aserciones disponibles:
```php
Transcription::assertGenerated(function (TranscriptionPrompt $prompt) {
    return $prompt->language === 'en' && $prompt->isDiarized();
});

Transcription::assertNotGenerated(
    fn (TranscriptionPrompt $prompt) => $prompt->language === 'fr'
);

Transcription::assertNothingGenerated();
```

Para transcripción encolada:
```php
use Laravel\Ai\Prompts\QueuedTranscriptionPrompt;

Transcription::assertQueued(
    fn (QueuedTranscriptionPrompt $prompt) => $prompt->isDiarized()
);

Transcription::assertNotQueued(
    fn (QueuedTranscriptionPrompt $prompt) => $prompt->language === 'fr'
);

Transcription::assertNothingQueued();
```

Para evitar transcripciones sin fake definido:
```php
Transcription::fake()->preventStrayTranscriptions();
```

**Próximo artículo:** Laravel AI SDK: Embeddings y Vector Stores