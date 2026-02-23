# Laravel AI SDK: Agents

El componente central del SDK es el agente. Un agente es una clase PHP que encapsula las instrucciones, el contexto de conversación, las herramientas disponibles y el esquema de salida para interactuar con un modelo de lenguaje.

Se crea con Artisan:
```bash
php artisan make:agent LaravelMentor
```

Esto genera una clase en `app/Ai/Agents/LaravelMentor.php` con la estructura base para implementar.

## Estructura de un agente

La clase generada implementa la interfaz `Agent` y usa el trait `Promptable`. A partir de ahí podemos implementar interfaces adicionales según lo que necesite el agente:
```php
<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

class LaravelMentor implements Agent
{
    use Promptable;

    public function instructions(): string
    {
        return 'You are a Laravel expert. You answer questions about the framework concisely and with practical examples.';
    }
}
```

Para prompting básico:
```php
$response = (new LaravelMentor)->prompt('Explicame brevemente el ciclo de vida de un request en Laravel.');

return (string) $response;
```

El método `make()` resuelve el agente desde el service container permitiendo inyección de dependencias:
```php
$response = LaravelMentor::make(user: $user)->prompt('Explicame brevemente el ciclo de vida de un request en Laravel.');
```

## Historial de conversación

Para que el agente tenga contexto de mensajes anteriores, implementamos la interfaz `Conversational` y definimos el método `messages()`:
```php
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Messages\Message;

class LaravelMentor implements Agent, Conversational
{
    use Promptable;

    public function messages(): iterable
    {
        return History::where('user_id', $this->user->id)
            ->latest()
            ->limit(50)
            ->get()
            ->reverse()
            ->map(fn ($message) => new Message($message->role, $message->content))
            ->all();
    }
}
```

Para no gestionar el historial manualmente, usamos el trait `RemembersConversations` que lo hace automáticamente. Persiste los mensajes en la base de datos y los carga en cada interacción:
```php
use Laravel\Ai\Concerns\RemembersConversations;

class LaravelMentor implements Agent, Conversational
{
    use Promptable, RemembersConversations;

    public function instructions(): string
    {
        return 'You are a Laravel expert...';
    }
}
```

Para iniciar una conversación nueva para un usuario:
```php
$response = (new LaravelMentor)->forUser($user)->prompt('Hola!');

$conversationId = $response->conversationId;
```

Para continuar una conversación existente:
```php
$response = (new LaravelMentor)
    ->continue($conversationId, as: $user)
    ->prompt('Contame más sobre eso.');
```

## Salida estructurada

Cuando necesitamos que el agente devuelva datos con una estructura definida en lugar de texto libre, implementamos `HasStructuredOutput` y definimos un `schema()`:
```php
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\HasStructuredOutput;

class LaravelMentor implements Agent, HasStructuredOutput
{
    use Promptable;

    public function schema(JsonSchema $schema): array
    {
        return [
            'feedback' => $schema->string()->required(),
            'score'    => $schema->integer()->min(1)->max(10)->required(),
        ];
    }
}
```

La respuesta se accede como un array:
```php
$response = (new LaravelMentor)->prompt('Explicame brevemente el ciclo de vida de un request en Laravel.');

$response['feedback'];
$response['score'];
```

## Configuración con atributos PHP

El provider, modelo, temperatura y otros parámetros se configuran directamente en la clase con *PHP attributes*:
```php
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Enums\Lab;

#[Provider(Lab::Anthropic)]
#[Model('claude-sonnet-4-5-20250929')]
#[MaxSteps(10)]
#[MaxTokens(4096)]
#[Temperature(0.7)]
#[Timeout(120)]
class LaravelMentor implements Agent
{
    use Promptable;
}
```

También hay dos *attributes* para selección automática de modelo sin especificar uno puntual:
```php
#[UseCheapestModel]
class SimpleSummarizer implements Agent
{
    use Promptable;
    // Usa el modelo más económico del provider configurado
}

#[UseSmartestModel]
class ComplexReasoner implements Agent
{
    use Promptable;
    // Usa el modelo más capaz del provider configurado
}
```

Estos parámetros también se pueden pasar directamente al momento de hacer el prompt, lo que sobrescribe la configuración de la clase:
```php
$response = (new LaravelMentor)->prompt(
    'Explicame brevemente el ciclo de vida de un request en Laravel.',
    provider: Lab::OpenAI,
    model: 'gpt-4o',
    timeout: 120,
);
```

## Middleware

Los agentes soportan middleware para interceptar y modificar prompts antes de que lleguen al provider. Implementamos `HasMiddleware` y definimos el método `middleware()`:
```php
use Laravel\Ai\Contracts\HasMiddleware;

class LaravelMentor implements Agent, HasMiddleware
{
    use Promptable;

    public function middleware(): array
    {
        return [
            new LogPrompts,
        ];
    }
}
```

Cada middleware define un método `handle()` que recibe el prompt y un closure para pasarlo al siguiente middleware:
```php
use Closure;
use Laravel\Ai\Prompts\AgentPrompt;

class LogPrompts
{
    public function handle(AgentPrompt $prompt, Closure $next)
    {
        Log::info('Prompt enviado', ['prompt' => $prompt->prompt]);

        return $next($prompt);
    }
}
```

El método `then()` en la respuesta nos permite ejecutar lógica después de que el agente terminó de procesar:
```php
public function handle(AgentPrompt $prompt, Closure $next)
{
    return $next($prompt)->then(function (AgentResponse $response) {
        Log::info('Respuesta recibida', ['text' => $response->text]);
    });
}
```

## Testing

El SDK incluye helpers para testear agentes sin hacer llamadas reales al provider:
```php
use App\Ai\Agents\LaravelMentor;

// Respuesta fija para todos los prompts
LaravelMentor::fake();

// Lista de respuestas en orden
LaravelMentor::fake([
    'Primera respuesta',
    'Segunda respuesta',
]);

// Respuesta dinámica basada en el prompt
LaravelMentor::fake(function (AgentPrompt $prompt) {
    return 'Respuesta para: ' . $prompt->prompt;
});
```

Y las aserciones correspondientes:
```php
LaravelMentor::assertPrompted('Explicame brevemente...');

LaravelMentor::assertPrompted(function (AgentPrompt $prompt) {
    return $prompt->contains('transcripción');
});

LaravelMentor::assertNeverPrompted();
```

**Próximo artículo:** Laravel AI SDK: Streaming, Tools y búsqueda semántica
