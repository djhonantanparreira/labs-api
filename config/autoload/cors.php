<?php

declare(strict_types=1);

use function Hyperf\Support\env;

return [
    'paths' => [
        '*', // Habilita CORS para todas as rotas
    ],
    'allowed_methods' => ['*'], // Permite todos os métodos (GET, POST, PUT, etc.)
    'allowed_origins' => [env('ALLOWED_ORIGINS', '*')], // Permite qualquer origem, ou defina 'http://localhost:5173'
    'allowed_origins_patterns' => [], // Não precisamos de padrões de origem adicionais
    'allowed_headers' => ['*'], // Permite todos os cabeçalhos
    'exposed_headers' => [], // Cabeçalhos expostos
    'max_age' => 0, // Sem cache para a resposta preflight
    'supports_credentials' => true, // Isso precisa estar 'true' se estiver usando cookies ou autenticação
];
