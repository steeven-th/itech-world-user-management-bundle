<?php

namespace ItechWorld\UserManagementBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use ItechWorld\UserManagementBundle\Filter\GlobalSearchFilter;
use ItechWorld\UserManagementBundle\State\UserPasswordHasher;
use ItechWorld\UserManagementBundle\Repository\UserRepository;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_USERNAME', fields: ['username'])]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['user:read']],
            security: "is_granted('CAN_VIEW_USERS')",
            formats: ['json']
        ),
        new Get(
            normalizationContext: ['groups' => ['user:read', 'user:details']],
            security: "is_granted('CAN_VIEW_USERS')",
            formats: ['json']
        ),
        new Post(
            denormalizationContext: ['groups' => ['user:write']],
            normalizationContext: ['groups' => ['user:read']],
            processor: UserPasswordHasher::class,
            security: "is_granted('CAN_CREATE_USERS')",
            formats: ['json']
        ),
        new Put(
            denormalizationContext: ['groups' => ['user:write']],
            normalizationContext: ['groups' => ['user:read']],
            processor: UserPasswordHasher::class,
            security: "is_granted('CAN_UPDATE_USERS')",
            formats: ['json']
        ),
        new Patch(
            denormalizationContext: ['groups' => ['user:write']],
            normalizationContext: ['groups' => ['user:read']],
            processor: UserPasswordHasher::class,
            security: "is_granted('CAN_UPDATE_USERS')",
            formats: ['json']
        ),
    ],
    formats: ['json']
)]
#[ApiFilter(GlobalSearchFilter::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read', 'user:details'])]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $username = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    #[Groups(['user:read'])]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    #[Groups(['user:write'])] // Seulement en écriture, jamais en lecture
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $firstName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $lastName = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['user:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, Permission>
     */
    #[ORM\ManyToMany(targetEntity: Permission::class, inversedBy: 'users')]
    #[ORM\JoinTable(name: 'user_permission')]
    #[Groups(['user:read', 'user:details'])]
    private Collection $permissions;

    #[ORM\ManyToOne(targetEntity: Group::class, inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    private ?Group $userGroup = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->permissions = new ArrayCollection();
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
    public function getUserIdentifier(): string
    {
        return (string)$this->username;
    }

    /**
     * @see UserInterface
     * Combine les rôles manuels avec le rôle automatique du groupe
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // Garantir que tout utilisateur a au moins ROLE_USER
        $roles[] = 'ROLE_USER';

        // Ajouter automatiquement le rôle du groupe si présent
        if ($this->userGroup) {
            $roles[] = $this->userGroup->getRole();
        }

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
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array)$this;
        $data["\0" . self::class . "\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, Permission>
     */
    public function getPermissions(): Collection
    {
        return $this->permissions;
    }

    public function addPermission(Permission $permission): static
    {
        if (!$this->permissions->contains($permission)) {
            $this->permissions->add($permission);
        }

        return $this;
    }

    public function removePermission(Permission $permission): static
    {
        $this->permissions->removeElement($permission);

        return $this;
    }

    public function getUserGroup(): ?Group
    {
        return $this->userGroup;
    }

    public function setUserGroup(?Group $userGroup): static
    {
        $this->userGroup = $userGroup;

        return $this;
    }

    /**
     * Vérifie si l'utilisateur a une permission spécifique (directe ou via le groupe)
     */
    public function hasPermission(string $resourceName, string $action): bool
    {
        // Vérifier les permissions directes de l'utilisateur
        foreach ($this->permissions as $permission) {
            if ($permission->getResource() &&
                $permission->getResource()->getName() === $resourceName &&
                $permission->getAction() === $action) {
                return true;
            }
        }

        // Vérifier les permissions du groupe (système de permissivité)
        if ($this->userGroup) {
            return $this->userGroup->hasPermission($resourceName, $action);
        }

        return false;
    }

    /**
     * Récupère toutes les permissions sous forme de codes (directes + groupe)
     * @return string[]
     */
    public function getPermissionCodes(): array
    {
        $codes = [];

        // Permissions directes de l'utilisateur
        foreach ($this->permissions as $permission) {
            $code = $permission->getCode();
            if ($code) {
                $codes[] = $code;
            }
        }

        // Permissions du groupe
        if ($this->userGroup) {
            foreach ($this->userGroup->getPermissions() as $permission) {
                $code = $permission->getCode();
                if ($code && !in_array($code, $codes)) {
                    $codes[] = $code;
                }
            }
        }

        return $codes;
    }

    /**
     * Récupère toutes les permissions effectives (directes + groupe)
     * @return Collection<int, Permission>
     */
    public function getAllPermissions(): Collection
    {
        $allPermissions = new ArrayCollection();

        // Ajouter les permissions directes
        foreach ($this->permissions as $permission) {
            $allPermissions->add($permission);
        }

        // Ajouter les permissions du groupe si elles ne sont pas déjà présentes
        if ($this->userGroup) {
            foreach ($this->userGroup->getPermissions() as $groupPermission) {
                if (!$allPermissions->contains($groupPermission)) {
                    $allPermissions->add($groupPermission);
                }
            }
        }

        return $allPermissions;
    }

    /**
     * Vérifie si l'utilisateur est administrateur
     */
    public function isAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->getRoles());
    }
}
