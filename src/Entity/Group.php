<?php

namespace ItechWorld\UserManagementBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use ItechWorld\UserManagementBundle\Repository\GroupRepository;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GroupRepository::class)]
#[ORM\Table(name: '`group`')] // 'group' est un mot réservé en SQL
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['group:read']],
            security: "is_granted('CAN_VIEW_PERMISSIONS')"
        ),
        new Get(
            normalizationContext: ['groups' => ['group:read', 'group:details']],
            security: "is_granted('CAN_VIEW_PERMISSIONS')"
        ),
        new Post(
            denormalizationContext: ['groups' => ['group:write']],
            normalizationContext: ['groups' => ['group:read']],
            security: "is_granted('CAN_CREATE_PERMISSIONS')"
        ),
        new Put(
            denormalizationContext: ['groups' => ['group:write']],
            normalizationContext: ['groups' => ['group:read']],
            security: "is_granted('CAN_UPDATE_PERMISSIONS')"
        ),
        new Delete(
            security: "is_granted('CAN_DELETE_PERMISSIONS') and object.name != 'ADMIN'"
        ),
    ]
)]
class Group
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group:read', 'group:details', 'user:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Groups(['group:read', 'group:write', 'user:read'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[Assert\Regex(pattern: '/^[A-Z_]+$/', message: 'Le nom du groupe doit être en majuscules et ne contenir que des lettres et des underscores')]
    private ?string $name = null;

    #[ORM\Column(length: 100)]
    #[Groups(['group:read', 'group:write', 'user:read'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private ?string $displayName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['group:read', 'group:write'])]
    #[Assert\Length(max: 255)]
    private ?string $description = null;

    /**
     * @var Collection<int, Permission>
     */
    #[ORM\ManyToMany(targetEntity: Permission::class)]
    #[ORM\JoinTable(name: 'group_permissions')]
    #[Groups(['group:details'])]
    private Collection $permissions;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'userGroup')]
    #[Groups(['group:details'])]
    private Collection $users;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['group:read'])]
    private bool $isSystem = false;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['group:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['group:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->permissions = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        // Le groupe ADMIN ne peut pas changer de nom
        if ($this->name === 'ADMIN' && $name !== 'ADMIN') {
            throw new \InvalidArgumentException('Le nom du groupe ADMIN ne peut pas être modifié');
        }

        $this->name = strtoupper($name);
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(string $displayName): static
    {
        $this->displayName = $displayName;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    /**
     * @return Collection<int, Permission>
     */
    public function getPermissions(): Collection
    {
        // Le groupe ADMIN a automatiquement toutes les permissions
        if ($this->name === 'ADMIN') {
            // Retourner toutes les permissions disponibles (simulation)
            return $this->permissions;
        }

        return $this->permissions;
    }

    public function addPermission(Permission $permission): static
    {
        if (!$this->permissions->contains($permission)) {
            $this->permissions->add($permission);
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function removePermission(Permission $permission): static
    {
        // Le groupe ADMIN ne peut pas perdre de permissions
        if ($this->name === 'ADMIN') {
            return $this;
        }

        if ($this->permissions->removeElement($permission)) {
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setUserGroup($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            if ($user->getUserGroup() === $this) {
                $user->setUserGroup(null);
            }
        }

        return $this;
    }

    public function isSystem(): bool
    {
        return $this->isSystem;
    }

    public function setIsSystem(bool $isSystem): static
    {
        $this->isSystem = $isSystem;

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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Vérifie si le groupe a une permission spécifique
     */
    public function hasPermission(string $resourceName, string $action): bool
    {
        // Le groupe ADMIN a toutes les permissions
        if ($this->name === 'ADMIN') {
            return true;
        }

        foreach ($this->permissions as $permission) {
            if ($permission->getResource()?->getName() === $resourceName &&
                $permission->getAction() === $action) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtient le rôle Symfony correspondant
     */
    public function getRole(): string
    {
        return 'ROLE_' . $this->name;
    }

    public function __toString(): string
    {
        return $this->displayName ?? $this->name ?? 'Groupe #' . $this->id;
    }
}
