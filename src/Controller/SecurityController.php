<?php

namespace App\Controller;

use App\Entity\AccessToken;
use App\Entity\User;
use App\Form\UserForm;
use App\Service\FormErrorService;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api', name: 'app_api_')]
#[Security(name: null)]
#[OA\Tag(name: 'SecurityController')]
#[OA\Response(
    response: Response::HTTP_BAD_REQUEST, description: 'Error',
    content: new OA\JsonContent(type: "object", example: ['errors' => []])
)]
final class SecurityController extends AbstractController
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

    #[Route('/register', name: 'register', methods: ['POST'])]
    #[OA\RequestBody(
        content: new Model(type: User::class, groups: ["create"])
    )]
    #[OA\Response(
        response: Response::HTTP_OK, description: 'Successful',
        content: new Model(type: User::class, groups: ["default"])
    )]
    public function register(
        Request                     $request,
        UserPasswordHasherInterface $passwordEncoder,
        EntityManagerInterface      $entityManager,
        FormErrorService            $formErrorService,
        SerializerInterface         $serializer
    ): JsonResponse
    {
        $user = new User();
        $form = $this->createForm(UserForm::class, $user);

        try {
            $data = $formErrorService->getRequestData($request);
        } catch (JsonException $e) {
            return $this->json(['errors' => 'Invalid Request Body'], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($data['password'])) {
            return $this->json(['errors' => 'Password cannot be empty'], Response::HTTP_BAD_REQUEST);
        }

        $form->submit($data, false);
        if ($form->isSubmitted() && $form->isValid()) {
            //ENCODE PASSWORD
            $user->setPassword(
                $passwordEncoder->hashPassword($user, $form->get('password')->getData())
            );
            $entityManager->persist($user);
            $entityManager->flush();

            return new JsonResponse(
                data: $serializer->serialize($user, 'json', ['groups' => 'default']),
                status: Response::HTTP_CREATED,
                json: true
            );
        }

        return $this->json($formErrorService->getErrorMessages($form), Response::HTTP_BAD_REQUEST);
    }
}
