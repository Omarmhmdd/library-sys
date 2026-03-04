<?php

return [
    'api_key' => env('COHERE_API_KEY'),
    'embed_model' => env('COHERE_EMBED_MODEL', 'embed-english-v3.0'),
    'chat_model' => env('COHERE_CHAT_MODEL', 'command-r-08-2024'),
    'embedding_dimensions' => (int) env('COHERE_EMBED_DIMENSIONS', 1024),
];
