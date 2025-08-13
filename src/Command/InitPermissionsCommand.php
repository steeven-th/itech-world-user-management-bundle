<?php

namespace ItechWorld\UserManagementBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use ItechWorld\UserManagementBundle\Entity\Permission;
use ItechWorld\UserManagementBundle\Entity\Resource;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'itech-world:init-permissions',
    description: 'Initialise les ressources et permissions de base',
    alias: ['i-w:init-permissions']
)]
class InitPermissionsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Initialisation des ressources et permissions');

        // Définir les ressources et actions
        $resourcesConfig = [
            'USERS' => [
                'description' => 'Gestion des utilisateurs',
                'actions' => [
                    'CREATE' => 'Créer des utilisateurs',
                    'READ' => 'Voir les utilisateurs',
                    'UPDATE' => 'Modifier les utilisateurs',
                    'DELETE' => 'Supprimer les utilisateurs'
                ]
            ],
            'GROUPS' => [
                'description' => 'Gestion des groupes et rôles',
                'actions' => [
                    'CREATE' => 'Créer des groupes',
                    'READ' => 'Voir les groupes',
                    'UPDATE' => 'Modifier les groupes',
                    'DELETE' => 'Supprimer les groupes'
                ]
            ],
            'PERMISSIONS' => [
                'description' => 'Gestion des permissions',
                'actions' => [
                    'CREATE' => 'Créer des permissions',
                    'READ' => 'Voir les permissions',
                    'UPDATE' => 'Modifier les permissions',
                    'DELETE' => 'Supprimer les permissions',
                    'MANAGE' => 'Gérer les permissions des utilisateurs'
                ]
            ]
        ];

        $createdResources = 0;
        $createdPermissions = 0;

        foreach ($resourcesConfig as $resourceName => $config) {
            // Créer ou récupérer la ressource
            $resource = $this->entityManager->getRepository(Resource::class)
                ->findByName($resourceName);

            if (!$resource) {
                $resource = new Resource();
                $resource->setName($resourceName);
                $resource->setDescription($config['description']);
                $this->entityManager->persist($resource);
                $this->entityManager->flush(); // Flush immédiatement pour avoir l'ID
                $createdResources++;
                $io->text("Ressource créée : {$resourceName}");
            } else {
                // Mettre à jour la description si nécessaire
                if ($resource->getDescription() !== $config['description']) {
                    $resource->setDescription($config['description']);
                    $io->text("Ressource mise à jour : {$resourceName}");
                }
            }

            // Créer les permissions pour cette ressource
            foreach ($config['actions'] as $action => $description) {
                $permission = $this->entityManager->getRepository(Permission::class)
                    ->findByResourceAndAction($resource, $action);

                if (!$permission) {
                    $permission = new Permission();
                    $permission->setResource($resource);
                    $permission->setAction($action);
                    $permission->setDescription($description);
                    $this->entityManager->persist($permission);
                    $createdPermissions++;
                    $io->text("Permission créée : {$resourceName}_{$action}");
                } else {
                    // Mettre à jour la description si nécessaire
                    if ($permission->getDescription() !== $description) {
                        $permission->setDescription($description);
                        $io->text("Permission mise à jour : {$resourceName}_{$action}");
                    }
                }
            }
        }

        // Sauvegarder en base
        $this->entityManager->flush();

        $io->success([
            'Initialisation terminée !',
            "Ressources créées : {$createdResources}",
            "Permissions créées : {$createdPermissions}",
        ]);

        $io->note([
            'Les permissions peuvent maintenant être assignées aux utilisateurs.',
            'Seuls les administrateurs (ROLE_ADMIN) peuvent gérer les permissions.'
        ]);

        return Command::SUCCESS;
    }
}
