<?php

namespace App\Controller;

use App\Entity\Content;
use App\Form\ContentForm;
use App\Repository\ContentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/content', name: 'app_content_')]
#[OA\Response(
    response: Response::HTTP_BAD_REQUEST, description: 'Error',
    content: new OA\JsonContent(type: "object", example: ['errors' => []])
)]
#[OA\Tag(name: 'ContentController')]
#[Security(name: "Bearer")]
final class ContentController extends AbstractController
{
    #[Route(name: 'index', methods: ['GET'])]
    public function index(
        ContentRepository $contentRepository
    ): JsonResponse
    {
        return $this->json($contentRepository->findAll(), Response::HTTP_OK);
    }

    #[Route(name: 'new', methods: ['POST'])]
    #[OA\RequestBody(
        content: new Model(type: Content::class, groups: ["create"])
    )]
    #[OA\Response(
        response: Response::HTTP_CREATED, description: 'Successful',
        content: new Model(type: Content::class, groups: ["default"])
    )]
    public function new(
        Request                $request,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $content = new Content();
        $form = $this->createForm(ContentForm::class, $content);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($content);
            $entityManager->flush();

            return $this->json($content, Response::HTTP_CREATED);
        }

        return $this->json($form->getErrors(), Response::HTTP_BAD_REQUEST);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(
        Content $content
    ): Response
    {
        return $this->json($content, Response::HTTP_OK);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['PUT'])]
    public function edit(
        Request                $request,
        Content                $content,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $form = $this->createForm(ContentForm::class, $content);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->json($content, Response::HTTP_SEE_OTHER);
        }

        return $this->json($form->getErrors(), Response::HTTP_BAD_REQUEST);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(
        Request                $request,
        Content                $content,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        if ($this->isCsrfTokenValid('delete' . $content->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($content);
            $entityManager->flush();
        }

        return $this->json($content, Response::HTTP_SEE_OTHER);
    }
}
