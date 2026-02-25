<?php

namespace Database\Seeders;

use App\Models\Document;
use Illuminate\Database\Seeder;
use Laravel\Ai\Embeddings;

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        $documents = [
            [
                'title' => 'Laravel Routing',
                'content' => 'Laravel provides a simple and expressive way to define routes. Routes are defined in the routes directory and are automatically loaded by the framework.',
            ],
            [
                'title' => 'Eloquent ORM',
                'content' => 'Eloquent is Laravel\'s built-in ORM that provides a beautiful ActiveRecord implementation for working with your database. Each table has a corresponding Model.',
            ],
            [
                'title' => 'Blade Templates',
                'content' => 'Blade is the templating engine included with Laravel. It provides template inheritance and sections for building layouts.',
            ],
            [
                'title' => 'Laravel Middleware',
                'content' => 'Middleware provides a mechanism for filtering HTTP requests entering your application. For example, authentication and CSRF protection.',
            ],
            [
                'title' => 'Artisan Console',
                'content' => 'Artisan is the command-line interface included with Laravel. It provides helpful commands for building your application, like migrations and seeders.',
            ],
            [
                'title' => 'Laravel Queues',
                'content' => 'Queues allow you to defer the processing of time-consuming tasks, like sending an email, until a later time. This speeds up web requests.',
            ],
            [
                'title' => 'Database Migrations',
                'content' => 'Migrations are like version control for your database. They allow you to define and share the application database schema definition.',
            ],
            [
                'title' => 'Laravel Validation',
                'content' => 'Laravel provides several approaches to validate incoming data. Form requests and the validate method make it easy to ensure data integrity.',
            ],
        ];

        $inputs = array_column($documents, 'content');
        $response = Embeddings::for($inputs)->dimensions(1536)->generate();

        foreach ($documents as $index => $document) {
            Document::create([
                'title' => $document['title'],
                'content' => $document['content'],
                'embedding' => $response->embeddings[$index],
            ]);
        }
    }
}
