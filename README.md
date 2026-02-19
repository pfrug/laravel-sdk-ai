# Laravel AI SDK - Ejemplos

Repo con ejemplos funcionales del [Laravel AI SDK](https://laravel.com/docs/12.x/ai-sdk). Acompaña una serie de artículos en LinkedIn sobre cómo usar el SDK en Laravel 12.

## Stack

- PHP 8.4
- Laravel 12
- PostgreSQL 17
- Docker

## Setup

```bash
git clone <repo-url> && cd laravel_ai
cp .env.example .env
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

Configurar las API keys en `.env`:

```env
ANTHROPIC_API_KEY=
GEMINI_API_KEY=
OPENAI_API_KEY=
```

## Endpoints

| Metodo | Ruta | Descripcion | Auth |
|--------|------|-------------|------|
| POST | `/api/token` | Genera token Sanctum | No |
| POST | `/api/agent/prompt` | Prompt basico a un agente anonimo | No |
| POST | `/api/agent/conversation` | Conversacion con historial (SalesCoach) | Si |
| POST | `/api/agent/structured` | Salida estructurada (SalesAnalyzer) | Si |

## Estructura

```
app/
├── Ai/
│   ├── Agents/
│   │   ├── SalesCoach.php          # Conversacional, middleware
│   │   └── SalesAnalyzer.php       # Salida estructurada
│   └── Middleware/
│       └── LogPrompts.php          # Log de prompts y respuestas
├── Http/
│   ├── Controllers/
│   │   └── AgentController.php
│   └── Requests/
│       ├── PromptRequest.php
│       ├── ConversationRequest.php
│       └── StructuredRequest.php
```

## Postman

La coleccion esta en `postman/Laravel_AI_SDK.postman_collection.json`. Importarla en Postman y ejecutar "Get Token" primero para autenticar automaticamente el resto de los requests.

## Articulos

1. [Introduccion](articles/01-introduccion.md)
2. [Agentes](articles/02-agents.md)
3. [Streaming, Tools y Busqueda Semantica](articles/03-streaming-tools-busqueda-semantica.md)
