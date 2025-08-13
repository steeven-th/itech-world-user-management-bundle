<?php

namespace ItechWorld\UserManagementBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use ItechWorld\UserManagementBundle\Entity\Permission;
use ItechWorld\UserManagementBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/users/{id}/permissions', name: 'user_permissions_')]
class UserPermissionsController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('', name: 'get', methods: ['GET'])]
    #[IsGranted('CAN_VIEW_USERS')]
    public function getUserPermissions(User $user): JsonResponse
    {
        $permissions = [];
        foreach ($user->getPermissions() as $permission) {
            $permissions[] = [
                'id' => $permission->getId(),
                'code' => $permission->getCode(),
                'resource' => $permission->getResource()?->getName(),
                'action' => $permission->getAction(),
                'description' => $permission->getDescription()
            ];
        }

        return $this->json([
            'user_id' => $user->getId(),
            'username' => $user->getUsername(),
            'permissions' => $permissions
        ]);
    }

    #[Route('', name: 'update', methods: ['PUT'])]
    #[IsGranted('CAN_UPDATE_USERS')]
    public function updateUserPermissions(User $user, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['permissions']) || !is_array($data['permissions'])) {
            return $this->json(['error' => 'Le champ permissions est requis et doit être un tableau'],
                Response::HTTP_BAD_REQUEST);
        }

        // Supprimer toutes les permissions actuelles
        $user->getPermissions()->clear();

        // Ajouter les nouvelles permissions
        $permissionRepository = $this->entityManager->getRepository(Permission::class);

        foreach ($data['permissions'] as $permissionId) {
            $permission = $permissionRepository->find($permissionId);
            if ($permission) {
                $user->addPermission($permission);
            }
        }

        $this->entityManager->flush();

        return $this->json([
            'message' => 'Permissions mises à jour avec succès',
            'user_id' => $user->getId(),
            'permissions_count' => $user->getPermissions()->count()
        ]);
    }

    #[Route('/{permissionId}', name: 'add', methods: ['POST'])]
    #[IsGranted('CAN_UPDATE_USERS')]
    public function addPermissionToUser(User $user, int $permissionId): JsonResponse
    {
        $permission = $this->entityManager->getRepository(Permission::class)->find($permissionId);

        if (!$permission) {
            return $this->json(['error' => 'Permission non trouvée'], Response::HTTP_NOT_FOUND);
        }

        if ($user->getPermissions()->contains($permission)) {
            return $this->json(['error' => 'L\'utilisateur a déjà cette permission'], Response::HTTP_CONFLICT);
        }

        $user->addPermission($permission);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Permission ajoutée avec succès',
            'permission' => [
                'id' => $permission->getId(),
                'code' => $permission->getCode()
            ]
        ]);
    }

    #[Route('/{permissionId}', name: 'remove', methods: ['DELETE'])]
    #[IsGranted('CAN_UPDATE_USERS')]
    public function removePermissionFromUser(User $user, int $permissionId): JsonResponse
    {
        $permission = $this->entityManager->getRepository(Permission::class)->find($permissionId);

        if (!$permission) {
            return $this->json(['error' => 'Permission non trouvée'], Response::HTTP_NOT_FOUND);
        }

        if (!$user->getPermissions()->contains($permission)) {
            return $this->json(['error' => 'L\'utilisateur n\'a pas cette permission'], Response::HTTP_NOT_FOUND);
        }

        $user->removePermission($permission);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Permission supprimée avec succès'
        ]);
    }

    #[Route('/check', name: 'check', methods: ['POST'])]
    #[IsGranted('CAN_VIEW_USERS')]
    public function checkUserPermission(User $user, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['resource']) || !isset($data['action'])) {
            return $this->json(['error' => 'Les champs resource et action sont requis'], Response::HTTP_BAD_REQUEST);
        }

        $hasPermission = $user->hasPermission($data['resource'], $data['action']);

        return $this->json([
            'user_id' => $user->getId(),
            'resource' => $data['resource'],
            'action' => $data['action'],
            'has_permission' => $hasPermission
        ]);
    }
}
