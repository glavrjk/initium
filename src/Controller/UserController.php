<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Service\FormHandlerService;
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
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/user', name: 'app_user_', format: 'json')]
#[IsGranted('ROLE_USER')]
#[Security(name: "Bearer")]
#[OA\Tag(name: 'UserController')]
#[OA\Response(
    response: Response::HTTP_BAD_REQUEST, description: 'Error',
    content: new OA\JsonContent(type: "object", example: ['errors' => []])
)]
#[OA\Response(
    response: Response::HTTP_OK, description: 'Successful',
    content: new Model(type: User::class, groups: ["default"])
)]
final class UserController extends AbstractController
{
    #[Route(name: 'app_user_show', methods: ['GET'])]
    public function show(
        SerializerInterface $serializer
    ): JsonResponse
    {
        return new JsonResponse(
            data: $serializer->serialize($this->getUser(), 'json', ['groups' => 'default']),
            status: Response::HTTP_CREATED,
            json: true
        );
    }

    #[OA\RequestBody(
        content: new Model(type: UserType::class, groups: ["create"])
    )]
    #[Route(name: 'app_user_edit', methods: [Request::METHOD_PUT])]
    public function edit(
        Request                     $request,
        UserPasswordHasherInterface $passwordEncoder,
        EntityManagerInterface      $entityManager,
        FormHandlerService          $formHandlerService,
        SerializerInterface         $serializer
    ): JsonResponse
    {
        $user = $this->getUser();
        $form = $this->createForm(UserType::class, $user, ['method' => Request::METHOD_PUT]);

        try {
            $formHandlerService->processRequest($request, $form);
        } catch (JsonException $e) {
            return $this->json(['errors' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        if ($user && $form->isSubmitted() && $form->isValid()) {
            if ($newPassword = $form->get('password')->getData()) {
                //ENCODE PASSWORD
                $user->setPassword(
                    $passwordEncoder->hashPassword($user, $newPassword)
                );
            }
            $entityManager->persist($user);
            $entityManager->flush();

            return new JsonResponse(
                data: $serializer->serialize($user, 'json', ['groups' => 'default']),
                status: Response::HTTP_OK,
                json: true
            );
        }

        return $this->json($formHandlerService->getErrorMessages($form), Response::HTTP_BAD_REQUEST);
    }

}
