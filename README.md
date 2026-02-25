# Laravel AI SDK - Ejemplos

Repo con ejemplos funcionales del [Laravel AI SDK](https://laravel.com/docs/12.x/ai-sdk). Acompaña una serie de artículos en LinkedIn sobre cómo usar el SDK en Laravel 12.

## Stack

- PHP 8.4
- Laravel 12
- PostgreSQL 17 + pgvector
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
| POST | `/api/agent/conversation` | Conversacion con historial (LaravelMentor) | Si |
| POST | `/api/agent/structured` | Salida estructurada (CodeReviewer) | Si |
| GET | `/api/agent/stream` | Streaming SSE (LaravelMentor) | No |
| POST | `/api/agent/tools` | Agente con tools (Calculator) | No |
| POST | `/api/agent/queue` | Procesamiento en background | No |
| POST | `/api/agent/image` | Generacion de imagen | No |
| POST | `/api/agent/image/queue` | Generacion de imagen en background | No |
| POST | `/api/agent/audio/tts` | Text-to-Speech | No |
| POST | `/api/agent/audio/stt` | Speech-to-Text (file upload) | No |
| POST | `/api/agent/embeddings` | Generacion de embeddings | No |
| POST | `/api/agent/vector-search` | Busqueda vectorial con pgvector | No |
| POST | `/api/agent/rerank` | Reranking de documentos | No |

## Estructura

```
app/
├── Ai/
│   ├── Agents/
│   │   ├── LaravelMentor.php        # Conversacional, middleware
│   │   ├── CodeReviewer.php         # Salida estructurada
│   │   └── Calculator.php          # Tools (RandomNumber, WebSearch)
│   ├── Tools/
│   │   └── RandomNumberGenerator.php
│   └── Middleware/
│       └── LogPrompts.php          # Log de prompts y respuestas
├── Http/
│   ├── Controllers/
│   │   ├── AgentController.php
│   │   ├── AudioController.php
│   │   ├── EmbeddingController.php
│   │   ├── ImageController.php
│   │   ├── StreamingController.php
│   │   └── ToolController.php
│   └── Requests/
│       ├── PromptRequest.php
│       ├── ConversationRequest.php
│       ├── StructuredRequest.php
│       ├── ToolRequest.php
│       ├── ImageRequest.php
│       ├── TtsRequest.php
│       ├── SttRequest.php
│       ├── EmbeddingRequest.php
│       ├── VectorSearchRequest.php
│       └── RerankRequest.php
```

## Postman

La coleccion esta en `postman/Laravel_AI_SDK.postman_collection.json`. Importarla en Postman y ejecutar "Get Token" primero para autenticar automaticamente el resto de los requests.

## Articulos

1. [Introduccion](articles/01-introduccion.md)
2. [Agentes](articles/02-agents.md)
3. [Streaming, Tools y Busqueda Semantica](articles/03-streaming-tools-busqueda-semantica.md)
4. [Generacion de imagenes](articles/04-generacion-imagenes.md)
5. [Audio TTS y STT](articles/05-audio-tts-stt.md)
6. [Embeddings y Vector Stores](articles/06-embeddings-vector-stores.md)
