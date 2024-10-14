<?php

declare(strict_types=1);

return [
    'http' => [
        \Gokure\HyperfCors\CorsMiddleware::class, // O middleware de CORS deve ser carregado primeiro
        \Hyperf\Validation\Middleware\ValidationMiddleware::class, // Outros middlewares seguem
    ],
];
