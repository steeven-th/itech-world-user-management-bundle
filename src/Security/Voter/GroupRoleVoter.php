<?php

namespace ItechWorld\UserManagementBundle\Security\Voter;

use ItechWorld\UserManagementBundle\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter pour les vérifications simples de rôles de groupe
 * Utilise les rôles Symfony synchronisés automatiquement pour de meilleures performances
 *
 * Exemple d'usage : @IsGranted('GROUP_ROLE', 'MODERATORS')
 */
class GroupRoleVoter extends Voter
{
    public const GROUP_ROLE = 'GROUP_ROLE';

    public function __construct(
        private Security $security
    ) {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return $attribute === self::GROUP_ROLE;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        // Les administrateurs ont accès à tout
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // $subject devrait être le nom du groupe (ex: 'MODERATORS')
        if (!is_string($subject)) {
            return false;
        }

        // Vérification rapide via les rôles Symfony synchronisés
        $roleToCheck = 'ROLE_' . strtoupper($subject);

        return $this->security->isGranted($roleToCheck);
    }
}
