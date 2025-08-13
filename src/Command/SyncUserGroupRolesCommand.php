<?php

namespace ItechWorld\UserManagementBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use ItechWorld\UserManagementBundle\Entity\User;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sync-user-group-roles',
    description: 'Synchronise les rôles des utilisateurs avec leurs groupes'
)]
class SyncUserGroupRolesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Synchronisation des rôles utilisateurs avec les groupes');

        $userRepository = $this->entityManager->getRepository(User::class);
        $users = $userRepository->findAll();

        $syncCount = 0;

        foreach ($users as $user) {
            $originalRoles = $user->getRoles();

            // Forcer le recalcul des rôles
            // getRoles() va automatiquement inclure le rôle du groupe
            $newRoles = $user->getRoles();

            if ($originalRoles !== $newRoles) {
                $io->text(sprintf(
                    'User: %s | Group: %s | Roles: %s',
                    $user->getUsername(),
                    $user->getUserGroup()?->getName() ?? 'None',
                    implode(', ', $newRoles)
                ));
                $syncCount++;
            }
        }

        // Pas besoin de flush car les rôles sont calculés dynamiquement

        $io->success(sprintf(
            'Synchronisation terminée. %d utilisateurs traités, %d modifiés.',
            count($users),
            $syncCount
        ));

        $io->note('Les rôles sont maintenant synchronisés automatiquement avec les groupes.');
        $io->note('Les rôles de groupe sont calculés dynamiquement - pas de stockage en base nécessaire.');

        return Command::SUCCESS;
    }
}
