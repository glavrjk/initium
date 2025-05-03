<?php

namespace App\Controller;

use App\Entity\AccessToken;
use App\Entity\Content;
use App\Entity\User;
use App\Repository\UserRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Nelmio\ApiDocBundle\Attribute\Security as NelmioSecurity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/api', name: 'app_api_')]
#[OA\Tag(name: 'DefaultController')]
#[OA\Response(
    response: Response::HTTP_BAD_REQUEST, description: 'Error',
    content: new OA\JsonContent(type: "object", example: ['errors' => []])
)]
#[NelmioSecurity(name: null)]
final class DefaultController extends AbstractController
{
    #[Route('/login', name: 'login', methods: ['POST'])]
    #[OA\Response(
        response: Response::HTTP_OK, description: 'Successful',
        content: new OA\JsonContent(type: "object", example: ['username' => "string", 'token' => "string"])
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(type: "object", example: ['username' => "string", 'password' => "string"])
    )]
    public function login(
        EntityManagerInterface $entityManager,
        #[CurrentUser] ?User   $user = null,
    ): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'message' => 'bad credentials',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $token = new AccessToken($user);
        $entityManager->persist($token);
        $entityManager->flush();

        return $this->json([
            'username' => $user->getUserIdentifier(),
            'token' => $token->getToken()
        ]);
    }
}
