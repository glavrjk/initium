<?php

namespace App\Controller;

use App\Entity\User;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api', name: 'app_api_')]
#[OA\Response(
    response: Response::HTTP_BAD_REQUEST,    description: 'Error',
    content: new OA\JsonContent(type: "object", example: ['errors' => []])
)]
#[OA\Tag(name: 'DefaultController')]
final class DefaultController extends AbstractController
{
    #[Route('/login', name: 'login', methods: ['POST'])]
    #[OA\RequestBody(
        content: new OA\JsonContent(type: "object", example: ['username' => "string", 'password' => "string"])
    )]
    #[OA\Response(
        response: Response::HTTP_OK,        description: 'Successful',
        content: new OA\JsonContent(type: "object", example: ['username' => "string", 'token' => "string"])
    )]
    public function login(
        #[MapQueryParameter] string $username,
        #[MapQueryParameter] string $password,
    ): JsonResponse
    {
        if (null === $user) {
            return $this->json([
                'message' => 'missing credentials',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $token = '';

        return $this->json([
            'username' => $user->getUserIdentifier(),
            'token' => $token,
        ]);
    }
}
