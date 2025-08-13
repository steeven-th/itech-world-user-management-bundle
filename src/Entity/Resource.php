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
use ItechWorld\UserManagementBundle\Repository\ResourceRepository;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ResourceRepository::class)]
#[ORM\Table(name: 'resource')]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['resource:read']],
            security: "is_granted('CAN_VIEW_RESOURCES')",
            formats: ['json']
        ),
        new Get(
            normalizationContext: ['groups' => ['resource:read', 'resource:details']],
            security: "is_granted('CAN_VIEW_RESOURCES')",
            formats: ['json']
        ),
        new Post(
            denormalizationContext: ['groups' => ['resource:write']],
            normalizationContext: ['groups' => ['resource:read']],
            security: "is_granted('CAN_CREATE_RESOURCES')",
            formats: ['json']
        ),
        new Put(
            denormalizationContext: ['groups' => ['resource:write']],
            normalizationContext: ['groups' => ['resource:read']],
            security: "is_granted('CAN_UPDATE_RESOURCES')",
            formats: ['json']
        ),
        new Delete(
            security: "is_granted('CAN_DELETE_RESOURCES')",
            formats: ['json']
        ),
    ],
    formats: ['json']
)]
class Resource
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['resource:read', 'resource:details', 'permission:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    #[Groups(['resource:read', 'permission:read'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['resource:read', 'resource:write', 'permission:read'])]
    #[Assert\Length(max: 255)]
    private ?string $displayName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['resource:read', 'resource:write', 'permission:read'])]
    #[Assert\Length(max: 255)]
    private ?string $description = null;

    /**
     * @var Collection<int, Permission>
     */
    #[ORM\OneToMany(targetEntity: Permission::class, mappedBy: 'resource', orphanRemoval: true)]
    #[Groups(['resource:details'])]
    private Collection $permissions;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['resource:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['resource:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->permissions = new ArrayCollection();
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
        $this->name = $name;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName ?? $this->name;
    }

    public function setDisplayName(?string $displayName): static
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
        return $this->permissions;
    }

    public function addPermission(Permission $permission): static
    {
        if (!$this->permissions->contains($permission)) {
            $this->permissions->add($permission);
            $permission->setResource($this);
        }

        return $this;
    }

    public function removePermission(Permission $permission): static
    {
        if ($this->permissions->removeElement($permission)) {
            // set the owning side to null (unless already changed)
            if ($permission->getResource() === $this) {
                $permission->setResource(null);
            }
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

    public function __toString(): string
    {
        return $this->name ?? 'Resource #' . $this->id;
    }
}
