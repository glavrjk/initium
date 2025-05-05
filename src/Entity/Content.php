<?php

namespace App\Entity;

use App\Repository\ContentRepository;
use DateTime;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\MaxDepth;
use Symfony\Component\Serializer\Attribute\SerializedPath;

#[ORM\Entity(repositoryClass: ContentRepository::class)]
#[ORM\Table(name: '`content`')]
class Content
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["default"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["default", "create"])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["default", "create"])]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(["default"])]
    private ?DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: User::class, fetch: 'EXTRA_LAZY', inversedBy: 'contents')]
    #[Ignore]
    private User|UserInterface $createdBy;

    #[ORM\OneToMany(targetEntity: Media::class, mappedBy: 'content', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(["default", "create"])]
    private Collection $medias;

    public function __construct(User|UserInterface $user)
    {
        $this->createdBy = $user;
        $this->createdAt = new DateTimeImmutable();
        $this->medias = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getCreatedBy(): User|UserInterface
    {
        return $this->createdBy;
    }

    public function setCreatedBy(User|UserInterface $createdBy): void
    {
        $this->createdBy = $createdBy;
    }

    public function getMedias(): Collection
    {
        return $this->medias;
    }

    public function addMedia(Media $medias): void
    {
        if (!$this->medias->contains($medias)) {
            $this->medias[] = $medias;
            $medias->setContent($this);
        }
    }

    public function removeMedia(Media $medias): void
    {
        if ($this->medias->contains($medias)) {
            $this->medias->removeElement($medias);
            if ($medias->getContent() === $this) {
                $medias->setContent(null);
            }
        }
    }

}
