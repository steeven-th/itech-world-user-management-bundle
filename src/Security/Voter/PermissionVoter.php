<?php

namespace ItechWorld\UserManagementBundle\Security\Voter;

use ItechWorld\UserManagementBundle\Entity\User;
use ItechWorld\UserManagementBundle\Repository\PermissionRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PermissionVoter extends Voter
{
    public const CAN_MANAGE_PERMISSIONS = 'CAN_MANAGE_PERMISSIONS';
    public const CAN_VIEW_PERMISSIONS = 'CAN_VIEW_PERMISSIONS';
    public const CAN_CREATE_PERMISSIONS = 'CAN_CREATE_PERMISSIONS';
    public const CAN_UPDATE_PERMISSIONS = 'CAN_UPDATE_PERMISSIONS';
    public const CAN_DELETE_PERMISSIONS = 'CAN_DELETE_PERMISSIONS';

    public const CAN_MANAGE_RESOURCES = 'CAN_MANAGE_RESOURCES';
    public const CAN_VIEW_RESOURCES = 'CAN_VIEW_RESOURCES';
    public const CAN_CREATE_RESOURCES = 'CAN_CREATE_RESOURCES';
    public const CAN_UPDATE_RESOURCES = 'CAN_UPDATE_RESOURCES';
    public const CAN_DELETE_RESOURCES = 'CAN_DELETE_RESOURCES';

    public const CAN_MANAGE_USERS = 'CAN_MANAGE_USERS';
    public const CAN_VIEW_USERS = 'CAN_VIEW_USERS';
    public const CAN_CREATE_USERS = 'CAN_CREATE_USERS';
    public const CAN_UPDATE_USERS = 'CAN_UPDATE_USERS';
    public const CAN_DELETE_USERS = 'CAN_DELETE_USERS';

    private const PERMISSIONS_MAP = [
        // Permissions
        self::CAN_MANAGE_PERMISSIONS => ['PERMISSIONS', 'MANAGE'],
        self::CAN_VIEW_PERMISSIONS => ['PERMISSIONS', 'READ'],
        self::CAN_CREATE_PERMISSIONS => ['PERMISSIONS', 'CREATE'],
        self::CAN_UPDATE_PERMISSIONS => ['PERMISSIONS', 'UPDATE'],
        self::CAN_DELETE_PERMISSIONS => ['PERMISSIONS', 'DELETE'],

        // Resources
        self::CAN_MANAGE_RESOURCES => ['PERMISSIONS', 'MANAGE'], // Gérer les ressources fait partie des permissions
        self::CAN_VIEW_RESOURCES => ['PERMISSIONS', 'READ'],
        self::CAN_CREATE_RESOURCES => ['PERMISSIONS', 'CREATE'],
        self::CAN_UPDATE_RESOURCES => ['PERMISSIONS', 'UPDATE'],
        self::CAN_DELETE_RESOURCES => ['PERMISSIONS', 'DELETE'],

        // Users
        self::CAN_MANAGE_USERS => ['USERS', 'MANAGE'],
        self::CAN_VIEW_USERS => ['USERS', 'READ'],
        self::CAN_CREATE_USERS => ['USERS', 'CREATE'],
        self::CAN_UPDATE_USERS => ['USERS', 'UPDATE'],
        self::CAN_DELETE_USERS => ['USERS', 'DELETE'],
    ];

    public function __construct(
        private Security $security,
        private PermissionRepository $permissionRepository
    ) {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return array_key_exists($attribute, self::PERMISSIONS_MAP);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        // Les administrateurs ont tous les droits par défaut
        // Maintenant grâce à la synchronisation automatique, on peut utiliser les rôles Symfony
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // Vérifier la permission spécifique
        if (!array_key_exists($attribute, self::PERMISSIONS_MAP)) {
            return false;
        }

        [$resourceName, $action] = self::PERMISSIONS_MAP[$attribute];

        // Méthode hybride : utiliser les permissions granulaires pour la flexibilité
        // Les rôles Symfony sont automatiquement synchronisés pour les cas simples
        return $user->hasPermission($resourceName, $action);
    }
}
