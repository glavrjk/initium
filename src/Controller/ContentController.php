<?php

namespace App\Controller;

use App\Entity\Content;
use App\Entity\ContentRate;
use App\Entity\Media;
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
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/content', name: 'app_content_', format: 'json')]
#[IsGranted('ROLE_USER')]
#[Security(name: "Bearer")]
#[OA\Tag(name: 'ContentController')]
#[OA\Response(
    response: Response::HTTP_BAD_REQUEST, description: 'Error',
    content: new OA\JsonContent(type: "object", example: ['errors' => []])
)]
final class ContentController extends AbstractController
{

    public function __construct(
        private readonly SerializerInterface    $serializer,
        private readonly EntityManagerInterface $entityManager,
        private readonly FormHandlerService     $formHandlerService,
        private readonly FileHandlerService     $fileHandlerService
    )
    {
    }

    #[Route(name: 'index', methods: ['GET'])]
    #[OA\Response(
        response: Response::HTTP_OK, description: 'Successful',
        content: new Model(type: Content::class, groups: ["default"])
    )]
    public function index(
        ContentRepository            $contentRepository,
        #[MapQueryParameter] ?string $title = null,
        #[MapQueryParameter] ?string $description = null
    ): JsonResponse
    {
        $data = $contentRepository->findContentsBy($title, $description);
        return new JsonResponse(data: $this->serializeContent($data), status: Response::HTTP_OK, json: true);
    }

    #[Route(name: 'new', methods: ['POST'])]
    #[OA\Response(
        response: Response::HTTP_CREATED, description: 'Successful',
        content: new Model(type: Content::class, groups: ["default"])
    )]
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
    public function new(Request $request): JsonResponse
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
                    $filename = $this->fileHandlerService->upload($mediaFile);
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

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[OA\Response(
        response: Response::HTTP_OK, description: 'Successful',
        content: new Model(type: Content::class, groups: ["default"])
    )]
    public function show(
        Content $content
    ): Response
    {
        return new JsonResponse(data: $this->serializeContent($content), status: Response::HTTP_OK, json: true);
    }

    #[Route('/{id}', name: 'edit', requirements: ['id' => '\d+'], methods: ['PUT'])]
    #[OA\Response(
        response: Response::HTTP_CREATED, description: 'Successful',
        content: new Model(type: Content::class, groups: ["default"])
    )]
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
    public function edit(Request $request, Content $content): JsonResponse
    {
        $form = $this->createForm(ContentType::class, $content, ['method' => Request::METHOD_PUT]);
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
                    $filename = $this->fileHandlerService->upload($mediaFile);
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

    #[OA\Response(
        response: Response::HTTP_OK, description: 'Successful',
        content: new OA\JsonContent(type: "string", example: 'Operation success')
    )]
    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(Content $content): JsonResponse
    {
        if ($this->getUser()->getUserIdentifier() === $content->getCreatedBy()->getUserIdentifier()) {
            foreach ($content->getMedias() as $media) {
                $this->fileHandlerService->remove($media->getFileName());
            }
            $this->entityManager->remove($content);
            $this->entityManager->flush();
            return $this->json('Operation success', Response::HTTP_OK);
        }

        return $this->json(['errors' => ['You are not allowed for this action']], Response::HTTP_FORBIDDEN);
    }

    #[OA\Response(
        response: Response::HTTP_OK, description: 'Successful',
        content: new OA\JsonContent(type: "string", example: 'Operation success')
    )]
    #[Route('/{id}/media/{mediaId}', name: 'media_delete', requirements: ['id' => '\d+', 'mediaId' => '\d+'], methods: ['DELETE'])]
    public function deleteMedia(
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
            return $this->json('Operation success', Response::HTTP_OK);
        }

        return $this->json(['errors' => ['You are not allowed for this action']], Response::HTTP_FORBIDDEN);
    }

    #[OA\Response(
        response: Response::HTTP_OK, description: 'Successful',
        content: new Model(type: Content::class, groups: ["default"])
    )]
    #[Route('/favorites', name: 'favorites', methods: ['GET'])]
    public function favorites(): JsonResponse
    {
        $data =$this->getUser()->getFavorites();
        return new JsonResponse(data: $this->serializeContent($data->toArray()), status: Response::HTTP_OK, json: true);
    }

    #[OA\Response(
        response: Response::HTTP_OK, description: 'Successful',
        content: new OA\JsonContent(type: "string", example: 'Operation success')
    )]
    #[Route('/{id}/favorite', name: 'add_favorite', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function addFavorite(Content $content): JsonResponse
    {
        if ($user = $this->getUser()) {
            $user->addFavorite($content);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
        return $this->json('Operation success', Response::HTTP_OK);
    }

    #[OA\Response(
        response: Response::HTTP_OK, description: 'Successful',
        content: new OA\JsonContent(type: "string", example: 'Operation success')
    )]
    #[Route('/{id}/favorite', name: 'remove_favorite', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function removeFavorite(Content $content): JsonResponse
    {
        if ($user = $this->getUser()) {
            $user->removeFavorite($content);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
        return $this->json('Operation success', Response::HTTP_OK);
    }

    #[OA\Response(
        response: Response::HTTP_OK, description: 'Successful',
        content: new OA\JsonContent(type: "string", example: 'Operation success')
    )]
    #[Route('/{id}/rate', name: 'rate', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function rate(
        Content                          $content,
        #[MapRequestPayload] ContentRate $contentRate
    ): JsonResponse
    {
        if ($user = $this->getUser()) {
            $contentRate->setContent($content);
            $contentRate->setUser($user);
            $this->entityManager->persist($contentRate);
            $this->entityManager->flush();
        }
        return $this->json('Operation success', Response::HTTP_OK);
    }

    public function serializeContent(Content|array $content): string
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
