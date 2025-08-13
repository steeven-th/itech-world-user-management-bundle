<?php

namespace ItechWorld\UserManagementBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use ItechWorld\UserManagementBundle\Entity\Group;
use ItechWorld\UserManagementBundle\Entity\Permission;
use ItechWorld\UserManagementBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/groups-stats', name: 'groups_stats_')]
class GroupsStatsController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/stats', name: 'stats', methods: ['GET'])]
    #[IsGranted('CAN_VIEW_PERMISSIONS')]
    public function getGroupsStats(): JsonResponse
    {
        $groupRepo = $this->entityManager->getRepository(Group::class);
        $userRepo = $this->entityManager->getRepository(User::class);

        // Statistiques générales
        $totalGroups = $groupRepo->count([]);
        $totalUsers = $userRepo->count([]);

        // Groupes avec nombre d'utilisateurs
        $groupsWithUsers = $this->entityManager->createQuery(
            'SELECT g.id, g.name, g.displayName, g.description, g.isSystem, COUNT(u.id) as user_count
             FROM App\Entity\Group g
             LEFT JOIN g.users u
             GROUP BY g.id, g.name, g.displayName, g.description, g.isSystem
             ORDER BY g.name ASC'
        )->getResult();

        // Groupes avec le plus de permissions
        $groupsWithPermissions = $this->entityManager->createQuery(
            'SELECT g.id, g.name, g.displayName, COUNT(p.id) as permissions_count
             FROM App\Entity\Group g
             LEFT JOIN g.permissions p
             GROUP BY g.id, g.name, g.displayName
             ORDER BY permissions_count DESC'
        )->setMaxResults(10)->getResult();

        return $this->json([
            'total_groups' => $totalGroups,
            'total_users' => $totalUsers,
            'groups_with_users' => $groupsWithUsers,
            'groups_with_permissions' => $groupsWithPermissions
        ]);
    }

    #[Route('/matrix', name: 'matrix', methods: ['GET'])]
    #[IsGranted('CAN_VIEW_PERMISSIONS')]
    public function getGroupsPermissionsMatrix(): JsonResponse
    {
        $groups = $this->entityManager->getRepository(Group::class)->findAllWithPermissions();
        $permissions = $this->entityManager->getRepository(Permission::class)->findAllWithResources();

        $matrix = [];

        foreach ($groups as $group) {
            $groupPermissions = [];
            foreach ($permissions as $permission) {
                $hasPermission = false;

                // Le groupe ADMIN a automatiquement toutes les permissions
                if ($group->getName() === 'ADMIN') {
                    $hasPermission = true;
                } else {
                    $hasPermission = $group->getPermissions()->contains($permission);
                }

                $groupPermissions[$permission->getCode()] = $hasPermission;
            }

            $matrix[] = [
                'group' => [
                    'id' => $group->getId(),
                    'name' => $group->getName(),
                    'displayName' => $group->getDisplayName(),
                    'isSystem' => $group->isSystem(),
                    'usersCount' => $group->getUsers()->count()
                ],
                'permissions' => $groupPermissions
            ];
        }

        $permissionsHeader = array_map(function($permission) {
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

    #[Route('/users-by-group', name: 'users_by_group', methods: ['GET'])]
    #[IsGranted('CAN_VIEW_PERMISSIONS')]
    public function getUsersByGroup(): JsonResponse
    {
        $groups = $this->entityManager->createQuery(
            'SELECT g.id, g.name, g.displayName, g.isSystem,
                    u.id as user_id, u.username, u.firstName, u.lastName
             FROM App\Entity\Group g
             LEFT JOIN g.users u
             ORDER BY g.name ASC, u.username ASC'
        )->getResult();

        // Regrouper les utilisateurs par groupe
        $groupedUsers = [];
        foreach ($groups as $row) {
            $groupId = $row['id'];

            if (!isset($groupedUsers[$groupId])) {
                $groupedUsers[$groupId] = [
                    'group' => [
                        'id' => $row['id'],
                        'name' => $row['name'],
                        'displayName' => $row['displayName'],
                        'isSystem' => $row['isSystem']
                    ],
                    'users' => []
                ];
            }

            if ($row['user_id']) {
                $groupedUsers[$groupId]['users'][] = [
                    'id' => $row['user_id'],
                    'username' => $row['username'],
                    'firstName' => $row['firstName'],
                    'lastName' => $row['lastName']
                ];
            }
        }

        return $this->json([
            'groups' => array_values($groupedUsers)
        ]);
    }
}
