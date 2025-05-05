<?php

namespace App\Controller;

use App\Entity\Content;
use App\Entity\Media;
use App\Service\FileHandlerService;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/content', name: 'app_content_')]
#[IsGranted('ROLE_USER')]
#[Security(name: "Bearer")]
#[OA\Tag(name: 'ContentController')]
#[OA\Response(
    response: Response::HTTP_BAD_REQUEST, description: 'Error',
    content: new OA\JsonContent(type: "object", example: ['errors' => []])
)]
#[OA\Response(
    response: Response::HTTP_CREATED, description: 'Successful',
    content: new OA\JsonContent(type: "string", example: 'Operation success')
)]
class MediaController extends AbstractController
{

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FileHandlerService     $fileHandlerService,
    )
    {
    }

    #[Route('/{id}/media/{mediaId}', name: 'media_delete', methods: ['DELETE'], format: 'json')]
    public function delete(
        Content $content,
        Media   $mediaId
    ): JsonResponse
    {
        if (
            $content->getId() === $mediaId->getContent()->getId() &&
            $this->getUser()->getUserIdentifier() === $content->getCreatedBy()->getUserIdentifier()
        ) {
            $this->fileHandlerService->remove($mediaId->getFileName());
            $this->entityManager->remove($mediaId);
            $this->entityManager->flush();
            return $this->json('Operation success', Response::HTTP_SEE_OTHER);
        }

        return $this->json(['errors' => ['You are not allowed for this action']], Response::HTTP_FORBIDDEN);
    }

}