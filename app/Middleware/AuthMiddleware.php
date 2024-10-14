<?php

namespace App\Middleware;

use App\Model\User;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Di\Container;
use Hyperf\Utils\Context;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Hyperf\Config\Annotation\Value;

class AuthMiddleware
{
    #[Value(key: "jwt_secret_key")]
    protected $jwtSecretKey;

    public function __construct(RequestInterface $request, ResponseInterface $response, Container $container)
    {
        $this->request = $request;
        $this->response = $response;
        $this->container = $container;
    }

    public function process($request, $handler)
    {
        $token = $this->request->getHeader('Authorization');

        if (empty($token)) {
            return $this->response->json(['error' => 'Token de autenticação ausente'], 401);
        }

        try {
            $token = explode(' ', $token[0]);
            $decoded = JWT::decode($token[1], new Key($this->jwtSecretKey, 'HS256'));

            // Supondo que o JWT contenha o UUID do usuário
            $userUuid = $decoded->uuid;

            // Buscar o usuário no banco de dados com o UUID decodificado
            $user = User::where('uuid', $userUuid)->first();

            if (!$user) {
                return $this->response->json(['error' => 'Usuário não encontrado'], 404);
            }

            // Coloca o usuário no container para ser usado nos controladores
            $this->container->set('user', $user);
        } catch (\Exception $e) {
            return $this->response->json(['error' => 'Token de autenticação inválido', 'message' => $e->getMessage()], 401);
        }

        return $handler->handle($request);
    }
}
