<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: '`media`')]
class Media
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    #[Groups(["default"])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Content::class, fetch: 'EXTRA_LAZY', inversedBy: 'medias')]
    private ?Content $content = null;

    #[ORM\Column]
    #[Groups(["default"])]
    private ?string $fileName = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getContent(): ?Content
    {
        return $this->content;
    }

    public function setContent(?Content $content): void
    {
        $this->content = $content;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(?string $fileName): void
    {
        $this->fileName = $fileName;
    }

}