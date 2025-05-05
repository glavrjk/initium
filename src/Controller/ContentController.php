<?php

namespace App\Controller;

use App\Entity\Content;
use App\Entity\Media;
use App\Entity\User;
use App\Form\ContentType;
use App\Repository\ContentRepository;
use App\Service\FileHandlerService;
use App\Service\FormHandlerService;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

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
    content: new Model(type: Content::class, groups: ["default"])
)]
final class ContentController extends AbstractController
{


    public function __construct(
        private readonly SerializerInterface    $serializer,
        private readonly EntityManagerInterface $entityManager,
        private readonly FormHandlerService     $formHandlerService,
    )
    {
    }

    #[Route(name: 'index', methods: ['GET'], format: 'json')]
    public function index(
        ContentRepository $contentRepository
    ): JsonResponse
    {
        return $this->json($contentRepository->findAll(), Response::HTTP_OK);
    }

    #[Route(name: 'new', methods: ['POST'], format: 'json')]
    #[OA\RequestBody(
        required: false,
        content: [new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                required: ['title', 'description'],
                properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(
                        property: 'mediaFiles[]', description: 'Files', type: 'array',
                        items: new OA\Items(type: 'file', format: 'binary')
                    )],
                type: 'object'
            )
        )]
    )]
    public function new(
        Request            $request,
        FileHandlerService $fileHandlerService
    ): JsonResponse
    {
        $content = new Content($this->getUser());
        $form = $this->createForm(ContentType::class, $content);

        try {
            $this->formHandlerService->processRequest($request, $form);
        } catch (JsonException $e) {
            return $this->json(['errors' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->entityManager->persist($content);
                foreach ($form->get('mediaFiles')->getData() as $mediaFile) {
                    $media = new Media();
                    $filename = $fileHandlerService->upload($mediaFile);
                    $media->setFileName($filename);
                    $content->addMedia($media);
                    $this->entityManager->persist($media);
                }
                $this->entityManager->flush();

                return new JsonResponse(data: $this->serializeContent($content), status: Response::HTTP_CREATED, json: true);

            } catch (FileException $e) {
                return $this->json(['errors' => $e->getMessage()], Response::HTTP_BAD_GATEWAY);
            }
        }

        return $this->json($this->formHandlerService->getErrorMessages($form), Response::HTTP_BAD_REQUEST);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], format: 'json')]
    public function show(
        Content $content
    ): Response
    {
        return new JsonResponse(data: $this->serializeContent($content), status: Response::HTTP_OK, json: true);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['PUT'])]
    public function edit(
        Request                $request,
        Content                $content,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $form = $this->createForm(ContentType::class, $content);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->json($content, Response::HTTP_SEE_OTHER);
        }

        return $this->json($form->getErrors(), Response::HTTP_BAD_REQUEST);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'], format: 'json')]
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

    public function serializeContent(
        Content $content
    ): string
    {
        $context = [
            AbstractNormalizer::GROUPS => 'default',
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (object $object, ?string $format, array $context): string {
                return $object->getId();
            },
        ];
        return $this->serializer->serialize($content, 'json', $context);
    }

}
