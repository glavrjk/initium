<?php

namespace App\Entity;

use App\Repository\AccessTokenRepository;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Random\RandomException;

#[ORM\Entity(repositoryClass: AccessTokenRepository::class)]
class AccessToken
{
    private const PERSONAL_ACCESS_TOKEN_PREFIX = "auth_";

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'accessTokens')]
    #[ORM\JoinColumn(nullable: false)]
    private User $ownedBy;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $expiresAt;

    #[ORM\Column(length: 255)]
    private string $token;

    public function __construct(
        User   $ownedBy,
        string $tokenType = self::PERSONAL_ACCESS_TOKEN_PREFIX
    )
    {
        $this->ownedBy = $ownedBy;
        $this->token = $tokenType . bin2hex(random_bytes(10));
        $this->expiresAt = (new DateTimeImmutable())->add(new DateInterval('PT2H'));
    }

    public function isValid(): bool
    {
        return $this->expiresAt === null || $this->expiresAt->getTimestamp() > time();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOwnedBy(): User
    {
        return $this->ownedBy;
    }

    public function setOwnedBy(User $ownedBy): static
    {
        $this->ownedBy = $ownedBy;

        return $this;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;

        return $this;
    }

}
