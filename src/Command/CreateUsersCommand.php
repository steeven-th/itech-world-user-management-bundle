<?php

namespace ItechWorld\UserManagementBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use ItechWorld\UserManagementBundle\Entity\User;
use ItechWorld\UserManagementBundle\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'itech-world:create-user',
    description: 'Crée un nouvel utilisateur',
    alias: ['i-w:create-users']
)]
class CreateUsersCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepository $userRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        // Plus besoin d'argument count
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🆕 Création d\'un nouvel utilisateur');

        // Définition des rôles disponibles
        $availableRoles = [
            'Utilisateur' => ['ROLE_USER'],
            'Admin' => ['ROLE_ADMIN']
        ];

        // Saisie du nom d'utilisateur avec vérification d'unicité
        do {
            $usernameQuestion = new Question('Nom d\'utilisateur : ');
            $usernameQuestion->setValidator(function ($value) {
                if (empty($value)) {
                    throw new \InvalidArgumentException('Le nom d\'utilisateur ne peut pas être vide');
                }
                if (strlen($value) < 3) {
                    throw new \InvalidArgumentException('Le nom d\'utilisateur doit contenir au moins 3 caractères');
                }
                return $value;
            });

            $username = $io->askQuestion($usernameQuestion);

            // Vérifier l'unicité
            $existingUser = $this->userRepository->findOneBy(['username' => $username]);
            if ($existingUser) {
                $io->error("❌ Le nom d'utilisateur '$username' existe déjà. Veuillez en choisir un autre.");
                $username = '';
            }
        } while (empty($username));

        // Saisie du mot de passe
        $passwordQuestion = new Question('Mot de passe : ');
        $passwordQuestion->setHidden(true);
        $passwordQuestion->setValidator(function ($value) {
            if (empty($value)) {
                throw new \InvalidArgumentException('Le mot de passe ne peut pas être vide');
            }
            if (strlen($value) < 6) {
                throw new \InvalidArgumentException('Le mot de passe doit contenir au moins 6 caractères');
            }
            return $value;
        });

        $plainPassword = $io->askQuestion($passwordQuestion);

        // Saisie des informations optionnelles
        $firstNameQuestion = new Question('Prénom (optionnel) : ');
        $firstName = $io->askQuestion($firstNameQuestion);

        $lastNameQuestion = new Question('Nom (optionnel) : ');
        $lastName = $io->askQuestion($lastNameQuestion);

        // Sélection du rôle
        $roleQuestion = new ChoiceQuestion(
            'Sélectionnez le rôle de l\'utilisateur :',
            array_keys($availableRoles),
            'Utilisateur'
        );

        $selectedRoleLabel = $io->askQuestion($roleQuestion);
        $selectedRoles = $availableRoles[$selectedRoleLabel];

        // Confirmation avant création
        $io->section('📋 Récapitulatif');
        $io->table(
            ['Propriété', 'Valeur'],
            [
                ['Nom d\'utilisateur', $username],
                ['Prénom', $firstName ?: 'Non renseigné'],
                ['Nom', $lastName ?: 'Non renseigné'],
                ['Rôle', $selectedRoleLabel],
                ['Mot de passe', str_repeat('*', strlen($plainPassword))]
            ]
        );

        if (!$io->confirm('Confirmer la création de cet utilisateur ?')) {
            $io->info('❌ Création annulée.');
            return Command::SUCCESS;
        }

        // Création de l'utilisateur
        $user = new User();
        $user->setUsername($username);
        $user->setRoles($selectedRoles);

        if ($firstName) {
            $user->setFirstName($firstName);
        }

        if ($lastName) {
            $user->setLastName($lastName);
        }

        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        $this->em->persist($user);
        $this->em->flush();

        $io->success("✅ Utilisateur '$username' créé avec succès avec le rôle '$selectedRoleLabel' !");

        return Command::SUCCESS;
    }
}
