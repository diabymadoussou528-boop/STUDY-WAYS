<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Tutor Provider (Groq — free tier)
    |--------------------------------------------------------------------------
    |
    | Get a free API key at https://console.groq.com/
    |
    */

    'provider' => env('AI_PROVIDER', 'gemini'),

    'groq' => [
        'api_key' => env('GROQ_API_KEY'),
        'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
        'model' => env('GROQ_MODEL', 'llama-3.1-8b-instant'),
        'max_tokens' => (int) env('GROQ_MAX_TOKENS', 1024),
        'temperature' => (float) env('GROQ_TEMPERATURE', 0.7),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
        'max_tokens' => (int) env('GEMINI_MAX_TOKENS', 1024),
        'temperature' => (float) env('GEMINI_TEMPERATURE', 0.7),
    ],

    'max_history' => (int) env('AI_MAX_HISTORY', 20),

];
