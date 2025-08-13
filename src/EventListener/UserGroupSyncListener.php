<?php

namespace ItechWorld\UserManagementBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use ItechWorld\UserManagementBundle\Entity\User;

/**
 * Synchronise automatiquement les rôles Symfony avec les groupes
 * pour bénéficier des performances des rôles tout en gardant la flexibilité des groupes
 */
#[AsDoctrineListener(event: Events::preUpdate)]
class UserGroupSyncListener
{
    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        // On s'occupe seulement des entités User
        if (!$entity instanceof User) {
            return;
        }

        // Vérifier si le groupe de l'utilisateur a changé
        if (!$args->hasChangedField('userGroup')) {
            return;
        }

        $oldGroup = $args->getOldValue('userGroup');
        $newGroup = $args->getNewValue('userGroup');

        // Log simple pour traçabilité (optionnel)
        if ($oldGroup && $newGroup) {
            error_log(sprintf(
                'User %s moved from group %s to %s',
                $entity->getUsername(),
                $oldGroup->getName(),
                $newGroup->getName()
            ));
        } elseif ($newGroup) {
            error_log(sprintf(
                'User %s added to group %s',
                $entity->getUsername(),
                $newGroup->getName()
            ));
        } elseif ($oldGroup) {
            error_log(sprintf(
                'User %s removed from group %s',
                $entity->getUsername(),
                $oldGroup->getName()
            ));
        }

        // Les rôles sont automatiquement calculés par la méthode getRoles()
        // Pas besoin de manipulation manuelle ici
    }
}
