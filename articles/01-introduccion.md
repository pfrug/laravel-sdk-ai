# Laravel AI SDK — Integración nativa de AI en Laravel 12

Hace unas semanas Laravel lanzó oficialmente su SDK para AI. Es un paquete que conecta Laravel con OpenAI, Anthropic, Gemini, xAI, Mistral, Groq, DeepSeek y otros providers desde una API unificada.

El SDK cubre texto, imágenes, audio (TTS y STT), embeddings y búsqueda semántica.

- Texto: agentes conversacionales con herramientas, salida estructurada y streaming
- Imágenes: generación con OpenAI, Gemini o xAI
- Audio TTS: síntesis de voz con OpenAI y ElevenLabs
- Audio STT: transcripción con OpenAI, ElevenLabs y Mistral
- Embeddings y búsqueda semántica: con soporte nativo para pgvector en PostgreSQL

## Instalación

Instalamos el paquete con Composer:
```bash
composer require laravel/ai
```

Después publicamos los archivos de configuración y migraciones del SDK usando el comando Artisan:
```bash
php artisan vendor:publish --provider="Laravel\Ai\AiServiceProvider"
```

Por último, corremos las migraciones. Esto crea las tablas `agent_conversations` y `agent_conversation_messages`, donde el SDK guarda el historial de conversaciones.
```bash
php artisan migrate
```

Configuramos las claves en `.env`:
```env
ANTHROPIC_API_KEY=
COHERE_API_KEY=
ELEVENLABS_API_KEY=
GEMINI_API_KEY=
MISTRAL_API_KEY=
OLLAMA_API_KEY=
OPENAI_API_KEY=
JINA_API_KEY=
VOYAGEAI_API_KEY=
XAI_API_KEY=
```

Solo declaramos las que vamos a usar, el resto puede quedar vacío.

## Agentes

El SDK tiene una función agent() que crea agentes anónimos, sin necesidad de una clase dedicada. Sirve para probar rápido o para casos simples:
```php
use function Laravel\Ai\{agent};

$response = agent(
    instructions: 'Sos un experto en desarrollo de software.',
)->prompt('Explicame qué es un Service Provider en Laravel');

return (string) $response;
```

### Elegir el provider en runtime

Podemos cambiar de provider sin modificar la lógica del agente:
```php
use Laravel\Ai\Enums\Lab;

$response = agent(
    instructions: 'Sos un experto en desarrollo de software.',
)->prompt(
    'Explicame qué es un Service Provider en Laravel',
    provider: Lab::Anthropic,
    model: 'claude-sonnet-4-5-20250929',
);
```

Contamos con el enum `Lab` que identifica cada provider disponible: `Lab::OpenAI`, `Lab::Anthropic`, `Lab::Gemini`, `Lab::Mistral`, `Lab::Groq`, etc.

## Integración con Laravel

El SDK se apoya en lo que Laravel ya ofrece: queues, filesystem, service container, eventos.

En los próximos artículos vamos a ver agentes con clase propia, historial persistente, herramientas, salida estructurada y generación de imágenes.

**Próximo artículo:** Laravel AI SDK - Agentes
En el próximo artículo voy a profundizar en el concepto de agentes como clases PHP: cómo definir sus instrucciones, gestionar el historial de conversación, estructurar salidas, configurar modelos mediante atributos y testear su comportamiento dentro de Laravel.