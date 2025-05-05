<?php

namespace App\Entity;

use App\Repository\ContentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ContentRepository::class)]
#[ORM\Table(name: '`content_rate`')]
class ContentRate
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Ignore]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, fetch: 'EXTRA_LAZY')]
    #[Ignore]
    private User|UserInterface $user;

    #[ORM\ManyToOne(targetEntity: Content::class, fetch: 'EXTRA_LAZY')]
    #[Ignore]
    private Content $content;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Groups(["default", "create"])]
    #[Assert\Range(notInRangeMessage: 'You must calificate between {{ min }} and {{ max }}', min: 1, max: 5)]
    private int $rate;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["default", "create"])]
    private ?string $comment = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getUser(): UserInterface|User
    {
        return $this->user;
    }

    public function setUser(UserInterface|User $user): void
    {
        $this->user = $user;
    }

    public function getContent(): Content
    {
        return $this->content;
    }

    public function setContent(Content $content): void
    {
        $this->content = $content;
    }

    public function getRate(): int
    {
        return $this->rate;
    }

    public function setRate(int $rate): void
    {
        $this->rate = $rate;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): void
    {
        $this->comment = $comment;
    }

}