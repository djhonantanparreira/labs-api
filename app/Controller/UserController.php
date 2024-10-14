<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Controller;

use App\Model\User;
use App\Repositories\LoginRepository;
use App\Request\UserRegisterRequest;
use Hyperf\Config\Annotation\Value;
use Hyperf\HttpServer\Contract\RequestInterface;
use Psr\Http\Message\ResponseInterface as Psr7ResponseInterface;

final class UserController extends AbstractController
{
    private $loginRepository;

    #[Value(key: 'register_token')]
    private $registerToken;

    #[Value(key: 'defaultPermissions.admin_default_permissions')]
    private $defaultAdminPermissions;

    #[Value(key: 'defaultPermissions.founder_default_permissions')]
    private $defaultFounderPermissions;

    public function __construct(
        LoginRepository $loginRepository,
    ) {
        $this->loginRepository = $loginRepository;
    }

    public function index()
    {
        $user = User::select(
            'uuid',
            'name',
            'cidade',
            'estado',
            'linkedin',
            'user_type',
            'permissions',
            'discord',
            'created_at',
            'updated_at'
        )->get();

        return $this->response->json($user);
    }

    public function create(UserRegisterRequest $request)
    {
        $token = $this->request->input('register_token');

        if ($token !== $this->registerToken) {
            return $this->response->json([
                'error' => 'Token inválido.',
            ], 403);
        }

        $result = $this->loginRepository->register($request);

        if ($result) {
            return $this->response->json([
                'message' => 'Usuário cadastrado com sucesso.',
            ])->withStatus(201);
        }

        return $this->response->json([
            'error' => 'Não foi possível realizar o cadastro.',
        ])->withStatus(500);
    }

    public function update(RequestInterface $request, $id)
    {
        $user = User::query()->where('uuid', $id)->first();

        if (empty($user)) {
            return $this->response->json([
                'error' => 'Usuário não encontrado.',
            ], 404);
        }

        if ($user->uuid !== $id) {
            return $this->response->json([
                'error' => 'Você não tem permissão para atualizar este usuário.',
            ], 403);
        }

        // Validação antes de atualizar
        $email = $request->input('email', $user->email);
        $name = $request->input('name', $user->name);
        $cidade = $request->input('cidade', $user->cidade);
        $estado = $request->input('estado', $user->estado);
        $linkedin = $request->input('linkedin', $user->linkedin);
        $discord = $request->input('discord', $user->discord);
        $password = $request->input('password');

        // Atualizando valores
        $user->name = $name;
        $user->email = $email;
        $user->linkedin = $linkedin;
        $user->cidade = $cidade;
        $user->estado = $estado;
        $user->discord = $discord;

        // Atualiza a senha apenas se o campo password for enviado
        if ($password) {
            $user->password = password_hash($password, PASSWORD_BCRYPT);
        }

        $user->save();

        return $this->response->json([
            'message' => 'Usuário atualizado com sucesso!',
            'user' => $user,
        ], 200);
    }

    public function updateUserType(RequestInterface $request, $id)
    {
        $user = User::query()->where('uuid', $id)->first();

        if (empty($user)) {
            return $this->response->json([
                'error' => 'Usuário não encontrado.',
            ], 404);
        }

        if ($user->uuid !== $id) {
            return $this->response->json([
                'error' => 'Você não tem permissão para atualizar este usuário.',
            ], 403);
        }

        if ($user->user_type !== 'admin') {
            return $this->response->json([
                'error' => 'Você não tem permissão para atualizar este usuário.',
            ], 403);
        }

        $userType = $this->request->input('user_type');

        $user->user_type = $userType;

        if ($userType == 'founder') {
            $user->permissions = serialize($this->defaultFounderPermissions);
        }

        if ($userType == 'admin') {
            $user->permissions = serialize($this->defaultAdminPermissions);
        }

        $user->save();

        return $this->response->json([
            'message' => 'Tipo de usuário atualizado com sucesso!',
            'user' => $user,
        ], 200);
    }

    public function delete($id): Psr7ResponseInterface
    {
        $user = $this->container->get('user');

        if ($user->uuid !== $id) {
            return $this->response->json([
                'error' => 'Você não tem permissão para deletar este usuário.',
            ], 403);
        }

        if (!$id) {
            return $this->response->json([
                'error' => 'O email é necessário para deletar o usuário.',
            ], 400);
        }

        $user = User::query()->where('uuid', $id)->first();

        if (!$user) {
            return $this->response->json([
                'error' => 'Usuário não encontrado.',
            ], 404);
        }

        $user->delete();

        return $this->response->json([
            'message' => 'Usuário deletado com sucesso!',
        ], 200);
    }

    public function permission(RequestInterface $request, $uuid)
    {
        $user = User::query()->where('uuid', $uuid)->first();

        if (empty($user)) {
            return $this->response->json([
                'error' => 'Usuário não encontrado.',
            ], 404);
        }

        // Se as permissões estiverem serializadas corretamente
        $permissions = @unserialize($user->permissions);

        if ($permissions === false || !is_array($permissions)) {
            return $this->response->json([
                'error' => 'Permissões inválidas.',
            ], 400);
        }

        if (!in_array('admin', $permissions)) {
            return $this->response->json([
                'error' => 'Você não tem permissão para atualizar este usuário.',
            ], 403);
        }

        $newPermissions = $request->input('permission');
        $user->permissions = serialize($newPermissions);
        $user->save();

        return $this->response->json([
            'message' => 'Permissões atualizadas com sucesso!',
            'user' => $user,
        ], 200);
    }


    public function alterUserPermission(RequestInterface $request, $uuid)
    {
        $user = User::query()->where('uuid', $uuid)->first();

        if (empty($user)) {
            return $this->response->json([
                'error' => 'Usuário não encontrado.',
            ], 404);
        }

        $user->permission = serialize($request->input('permissions'));
        $user->save();  // Adicionando os parênteses aqui

        return $this->response->json([
            'message' => 'Permissões de usuário atualizadas com sucesso!',
            'user' => $user,
            'status' => 200
        ], 200);
    }
}
