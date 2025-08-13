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
use ItechWorld\UserManagementBundle\Repository\PermissionRepository;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PermissionRepository::class)]
#[ORM\Table(name: 'permission')]
#[ORM\UniqueConstraint(name: 'UNIQ_RESOURCE_ACTION', columns: ['resource_id', 'action'])]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['permission:read']],
            security: "is_granted('CAN_VIEW_PERMISSIONS')",
            formats: ['json']
        ),
        new Get(
            normalizationContext: ['groups' => ['permission:read', 'permission:details']],
            security: "is_granted('CAN_VIEW_PERMISSIONS')",
            formats: ['json']
        ),
        new Post(
            denormalizationContext: ['groups' => ['permission:write']],
            normalizationContext: ['groups' => ['permission:read']],
            security: "is_granted('CAN_CREATE_PERMISSIONS')",
            formats: ['json']
        ),
        new Put(
            denormalizationContext: ['groups' => ['permission:write']],
            normalizationContext: ['groups' => ['permission:read']],
            security: "is_granted('CAN_UPDATE_PERMISSIONS')",
            formats: ['json']
        ),
        new Delete(
            security: "is_granted('CAN_DELETE_PERMISSIONS')",
            formats: ['json']
        ),
    ],
    formats: ['json']
)]
class Permission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['permission:read', 'permission:details', 'user:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'permissions')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['permission:read', 'permission:write'])]
    #[Assert\NotNull]
    private ?Resource $resource = null;

    #[ORM\Column(length: 100)]
    #[Groups(['permission:read', 'permission:write', 'user:read'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[Assert\Choice(choices: ['CREATE', 'READ', 'UPDATE', 'DELETE', 'MANAGE'])]
    private ?string $action = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['permission:read', 'permission:write'])]
    #[Assert\Length(max: 255)]
    private ?string $description = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'permissions')]
    #[Groups(['permission:details'])]
    private Collection $users;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['permission:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['permission:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getResource(): ?Resource
    {
        return $this->resource;
    }

    public function setResource(?Resource $resource): static
    {
        $this->resource = $resource;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;
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
            $user->addPermission($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            $user->removePermission($this);
        }

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
     * Génère un code unique pour cette permission
     * Format: RESOURCE_ACTION (ex: USERS_CREATE, GROUPS_READ)
     */
    public function getCode(): ?string
    {
        if (!$this->resource || !$this->action) {
            return null;
        }

        return strtoupper($this->resource->getName()) . '_' . strtoupper($this->action);
    }

    public function __toString(): string
    {
        return $this->getCode() ?? 'Permission #' . $this->id;
    }
}
