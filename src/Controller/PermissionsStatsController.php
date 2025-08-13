<?php

namespace ItechWorld\UserManagementBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use ItechWorld\UserManagementBundle\Entity\Permission;
use ItechWorld\UserManagementBundle\Entity\Resource;
use ItechWorld\UserManagementBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/permissions-stats', name: 'permissions_stats_')]
class PermissionsStatsController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/stats', name: 'stats', methods: ['GET'])]
    #[IsGranted('CAN_VIEW_PERMISSIONS')]
    public function getPermissionsStats(): JsonResponse
    {
        $resourceRepo = $this->entityManager->getRepository(Resource::class);
        $permissionRepo = $this->entityManager->getRepository(Permission::class);
        $userRepo = $this->entityManager->getRepository(User::class);

        // Statistiques générales
        $totalResources = $resourceRepo->count([]);
        $totalPermissions = $permissionRepo->count([]);
        $totalUsers = $userRepo->count([]);

        // Permissions par ressource
        $permissionsByResource = [];
        $resources = $resourceRepo->findAllWithPermissions();

        foreach ($resources as $resource) {
            $permissionsByResource[] = [
                'resource' => $resource->getName(),
                'description' => $resource->getDescription(),
                'permissions_count' => $resource->getPermissions()->count(),
                'permissions' => array_map(function ($permission) {
                    return [
                        'id' => $permission->getId(),
                        'action' => $permission->getAction(),
                        'description' => $permission->getDescription(),
                        'users_count' => $permission->getUsers()->count()
                    ];
                }, $resource->getPermissions()->toArray())
            ];
        }

        // Utilisateurs avec le plus de permissions
        $topUsers = $this->entityManager->createQuery(
            'SELECT u.id, u.username, u.firstName, u.lastName, COUNT(p.id) as permissions_count
             FROM ItechWorld\UserManagementBundle\Entity\User u
             LEFT JOIN u.permissions p
             GROUP BY u.id, u.username, u.firstName, u.lastName
             ORDER BY permissions_count DESC'
        )->setMaxResults(10)->getResult();

        return $this->json([
            'total_resources' => $totalResources,
            'total_permissions' => $totalPermissions,
            'total_users' => $totalUsers,
            'permissions_by_resource' => $permissionsByResource,
            'top_users' => $topUsers
        ]);
    }

    #[Route('/matrix', name: 'matrix', methods: ['GET'])]
    #[IsGranted('CAN_VIEW_PERMISSIONS')]
    public function getPermissionsMatrix(): JsonResponse
    {
        $users = $this->entityManager->getRepository(User::class)->findAll();
        $permissions = $this->entityManager->getRepository(Permission::class)->findAllWithResources();

        $matrix = [];

        foreach ($users as $user) {
            $userPermissions = [];
            foreach ($permissions as $permission) {
                $userPermissions[$permission->getCode()] = $user->getPermissions()->contains($permission);
            }

            $matrix[] = [
                'user' => [
                    'id' => $user->getId(),
                    'username' => $user->getUsername(),
                    'fullName' => trim($user->getFirstName() . ' ' . $user->getLastName()),
                    'roles' => $user->getRoles()
                ],
                'permissions' => $userPermissions
            ];
        }

        $permissionsHeader = array_map(function ($permission) {
            return [
                'code' => $permission->getCode(),
                'resource' => $permission->getResource()->getName(),
                'action' => $permission->getAction(),
                'description' => $permission->getDescription()
            ];
        }, $permissions);

        return $this->json([
            'permissions' => $permissionsHeader,
            'matrix' => $matrix
        ]);
    }
}
