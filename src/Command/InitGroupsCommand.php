<?php

namespace ItechWorld\UserManagementBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use ItechWorld\UserManagementBundle\Entity\Group;
use ItechWorld\UserManagementBundle\Entity\Permission;
use ItechWorld\UserManagementBundle\Repository\GroupRepository;
use ItechWorld\UserManagementBundle\Repository\PermissionRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'itech-world:init-groups',
    description: 'Initialise les groupes par défaut du système',
    alias: ['i-w:init-groups']
)]
class InitGroupsCommand extends Command
{

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GroupRepository $groupRepository,
        private readonly PermissionRepository $permissionRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Initialisation des groupes système');

        $createdGroups = 0;

        // Créer le groupe ADMIN s'il n'existe pas
        $adminGroup = $this->groupRepository->findByName('ADMIN');
        if (!$adminGroup) {
            $adminGroup = new Group();
            $adminGroup->setName('ADMIN');
            $adminGroup->setDisplayName('Administrateurs');
            $adminGroup->setDescription('Groupe système avec tous les droits. Ne peut pas être supprimé.');
            $adminGroup->setIsSystem(true);

            // Assigner toutes les permissions au groupe ADMIN
            $allPermissions = $this->permissionRepository->findAll();
            foreach ($allPermissions as $permission) {
                $adminGroup->addPermission($permission);
            }

            $this->entityManager->persist($adminGroup);
            $createdGroups++;
            $io->success('Groupe ADMIN créé avec toutes les permissions');
        } else {
            // S'assurer que le groupe ADMIN a toutes les permissions
            $allPermissions = $this->permissionRepository->findAll();
            $addedPermissions = 0;

            foreach ($allPermissions as $permission) {
                if (!$adminGroup->getPermissions()->contains($permission)) {
                    $adminGroup->addPermission($permission);
                    $addedPermissions++;
                }
            }

            if ($addedPermissions > 0) {
                $io->note("Ajout de {$addedPermissions} permissions manquantes au groupe ADMIN");
            } else {
                $io->note('Groupe ADMIN déjà à jour');
            }
        }

        // Créer d'autres groupes par défaut
        $defaultGroups = [
            [
                'name' => 'USER',
                'displayName' => 'Utilisateurs',
                'description' => 'Groupe par défaut pour les utilisateurs standards',
                'permissions' => ['USERS_READ'] // Permissions de base
            ],
            [
                'name' => 'MODERATOR',
                'displayName' => 'Modérateurs',
                'description' => 'Groupe avec des permissions étendues pour la modération',
                'permissions' => ['USERS_READ', 'USERS_UPDATE', 'PERMISSIONS_READ']
            ]
        ];

        foreach ($defaultGroups as $groupData) {
            $existingGroup = $this->groupRepository->findByName($groupData['name']);
            if (!$existingGroup) {
                $group = new Group();
                $group->setName($groupData['name']);
                $group->setDisplayName($groupData['displayName']);
                $group->setDescription($groupData['description']);
                $group->setIsSystem(false);

                // Ajouter les permissions spécifiées
                foreach ($groupData['permissions'] as $permissionCode) {
                    // Décomposer le code (ex: USERS_READ -> USERS + READ)
                    $parts = explode('_', $permissionCode);
                    if (count($parts) >= 2) {
                        $resourceName = $parts[0];
                        $action = $parts[1];

                        $permission = $this->entityManager->createQuery(
                            'SELECT p FROM App\Entity\Permission p 
                             JOIN p.resource r 
                             WHERE r.name = :resourceName AND p.action = :action'
                        )
                            ->setParameter('resourceName', $resourceName)
                            ->setParameter('action', $action)
                            ->getOneOrNullResult();

                        if ($permission) {
                            $group->addPermission($permission);
                        }
                    }
                }

                $this->entityManager->persist($group);
                $createdGroups++;
                $io->success("Groupe {$groupData['name']} créé");
            }
        }

        if ($createdGroups > 0) {
            $this->entityManager->flush();
        }

        $io->success('Initialisation terminée !');
        $io->table(
            ['Statistique', 'Valeur'],
            [
                ['Groupes créés', $createdGroups],
            ]
        );

        $io->note([
            'Les groupes peuvent maintenant être utilisés pour organiser les permissions.',
            '',
            'Le groupe ADMIN possède automatiquement toutes les permissions.',
            'Les autres groupes peuvent être personnalisés selon vos besoins.'
        ]);

        return Command::SUCCESS;
    }
}
