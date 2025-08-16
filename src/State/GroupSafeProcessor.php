<?php

namespace ItechWorld\UserManagementBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ItechWorld\UserManagementBundle\Entity\Group;
use Doctrine\ORM\EntityManagerInterface;

class GroupSafeProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProcessorInterface $decorated
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof Group) {
            return $this->decorated->process($data, $operation, $uriVariables, $context);
        }

        // Récupérer l'entité originale avec ses permissions
        $originalGroup = $this->entityManager->find(Group::class, $uriVariables['id']);

        if ($originalGroup) {
            // Préserver les permissions de l'original
            $originalPermissions = $originalGroup->getPermissions()->toArray();

            // Vider et remettre les permissions
            $data->getPermissions()->clear();
            foreach ($originalPermissions as $permission) {
                $data->addPermission($permission);
            }
        }

        return $this->decorated->process($data, $operation, $uriVariables, $context);
    }
}
