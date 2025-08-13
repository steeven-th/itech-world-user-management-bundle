<?php

namespace ItechWorld\UserManagementBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use ItechWorld\UserManagementBundle\Entity\Group;
use ItechWorld\UserManagementBundle\Entity\Permission;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/groups/{id}/permissions', name: 'group_permissions_')]
class GroupPermissionsController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('', name: 'get', methods: ['GET'])]
    #[IsGranted('CAN_VIEW_PERMISSIONS')]
    public function getGroupPermissions(Group $group): JsonResponse
    {
        $permissions = [];
        foreach ($group->getPermissions() as $permission) {
            $permissions[] = [
                'id' => $permission->getId(),
                'code' => $permission->getCode(),
                'resource' => $permission->getResource()?->getName(),
                'action' => $permission->getAction(),
                'description' => $permission->getDescription()
            ];
        }

        return $this->json([
            'group_id' => $group->getId(),
            'group_name' => $group->getName(),
            'display_name' => $group->getDisplayName(),
            'permissions' => $permissions,
            'is_system' => $group->isSystem()
        ]);
    }

    #[Route('', name: 'update', methods: ['PUT'])]
    #[IsGranted('CAN_UPDATE_PERMISSIONS')]
    public function updateGroupPermissions(Group $group, Request $request): JsonResponse
    {
        // Le groupe ADMIN ne peut pas perdre ses permissions
        if ($group->getName() === 'ADMIN') {
            return $this->json(['error' => 'Les permissions du groupe ADMIN ne peuvent pas être modifiées'],
                Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['permissions']) || !is_array($data['permissions'])) {
            return $this->json(['error' => 'Le champ permissions est requis et doit être un tableau'],
                Response::HTTP_BAD_REQUEST);
        }

        // Supprimer toutes les permissions actuelles
        $group->getPermissions()->clear();

        // Ajouter les nouvelles permissions
        $permissionRepository = $this->entityManager->getRepository(Permission::class);

        foreach ($data['permissions'] as $permissionId) {
            $permission = $permissionRepository->find($permissionId);
            if ($permission) {
                $group->addPermission($permission);
            }
        }

        $this->entityManager->flush();

        return $this->json([
            'message' => 'Permissions du groupe mises à jour avec succès',
            'group_id' => $group->getId(),
            'permissions_count' => $group->getPermissions()->count()
        ]);
    }

    #[Route('/{permissionId}', name: 'add', methods: ['POST'])]
    #[IsGranted('CAN_UPDATE_PERMISSIONS')]
    public function addPermissionToGroup(Group $group, int $permissionId): JsonResponse
    {
        $permission = $this->entityManager->getRepository(Permission::class)->find($permissionId);

        if (!$permission) {
            return $this->json(['error' => 'Permission non trouvée'], Response::HTTP_NOT_FOUND);
        }

        if ($group->getPermissions()->contains($permission)) {
            return $this->json(['error' => 'Le groupe a déjà cette permission'], Response::HTTP_CONFLICT);
        }

        $group->addPermission($permission);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Permission ajoutée au groupe avec succès',
            'permission' => [
                'id' => $permission->getId(),
                'code' => $permission->getCode()
            ]
        ]);
    }

    #[Route('/{permissionId}', name: 'remove', methods: ['DELETE'])]
    #[IsGranted('CAN_UPDATE_PERMISSIONS')]
    public function removePermissionFromGroup(Group $group, int $permissionId): JsonResponse
    {
        // Le groupe ADMIN ne peut pas perdre de permissions
        if ($group->getName() === 'ADMIN') {
            return $this->json(['error' => 'Impossible de supprimer des permissions du groupe ADMIN'],
                Response::HTTP_FORBIDDEN);
        }

        $permission = $this->entityManager->getRepository(Permission::class)->find($permissionId);

        if (!$permission) {
            return $this->json(['error' => 'Permission non trouvée'], Response::HTTP_NOT_FOUND);
        }

        if (!$group->getPermissions()->contains($permission)) {
            return $this->json(['error' => 'Le groupe n\'a pas cette permission'], Response::HTTP_NOT_FOUND);
        }

        $group->removePermission($permission);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Permission supprimée du groupe avec succès'
        ]);
    }

    #[Route('/check', name: 'check', methods: ['POST'])]
    #[IsGranted('CAN_VIEW_PERMISSIONS')]
    public function checkGroupPermission(Group $group, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['resource']) || !isset($data['action'])) {
            return $this->json(['error' => 'Les champs resource et action sont requis'], Response::HTTP_BAD_REQUEST);
        }

        $hasPermission = $group->hasPermission($data['resource'], $data['action']);

        return $this->json([
            'group_id' => $group->getId(),
            'group_name' => $group->getName(),
            'resource' => $data['resource'],
            'action' => $data['action'],
            'has_permission' => $hasPermission
        ]);
    }
}
