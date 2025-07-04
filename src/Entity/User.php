<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinTable;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_USERNAME', fields: ['username'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Ignore]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank]
    #[Groups(["default", "create"])]
    private ?string $username = null;

    #[ORM\Column(type: Types::STRING, length: 180)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Groups(["default", "create"])]
    private ?string $email = null;

    #[ORM\Column]
    #[Ignore]
    private array $roles;

    #[ORM\Column]
    #[Groups(["create"])]
    private ?string $password = null;

    #[ORM\OneToMany(targetEntity: Content::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Ignore]
    private Collection $contents;

    #[ORM\ManyToMany(targetEntity: Content::class)]
    #[JoinTable(name: '`user_favorites`')]
    #[Ignore]
    private Collection $favorites;

    #[ORM\OneToMany(targetEntity: AccessToken::class, mappedBy: 'ownedBy', orphanRemoval: true)]
    #[Ignore]
    private Collection $accessTokens;

    public function __construct()
    {
        $this->contents = new ArrayCollection();
        $this->accessTokens = new ArrayCollection();
        $this->favorites = new ArrayCollection();
        //DEFAULT ROLE
        $this->roles = ['ROLE_USER'];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    #[Ignore]
    public function getUserIdentifier(): string
    {
        return (string)$this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getContents(): Collection
    {
        return $this->contents;
    }

    public function getAccessTokens(): Collection
    {
        return $this->accessTokens;
    }

    public function getFavorites(): Collection
    {
        return $this->favorites;
    }

    public function addFavorite(Content $content): void
    {
        if (!$this->favorites->contains($content)) {
            $this->favorites[] = $content;
        }
    }

    public function removeFavorite(Content $content): void
    {
        if ($this->favorites->contains($content)) {
            $this->favorites->removeElement($content);
        }
    }

}
