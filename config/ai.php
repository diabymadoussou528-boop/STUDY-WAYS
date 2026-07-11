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

    'provider' => env('AI_PROVIDER', 'groq'),

    'groq' => [
        'api_key' => env('GROQ_API_KEY'),
        'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
        'model' => env('GROQ_MODEL', 'llama-3.1-8b-instant'),
        'max_tokens' => (int) env('GROQ_MAX_TOKENS', 1024),
        'temperature' => (float) env('GROQ_TEMPERATURE', 0.7),
    ],

    'max_history' => (int) env('AI_MAX_HISTORY', 20),

];
