<?php

namespace ItechWorld\UserManagementBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use ItechWorld\UserManagementBundle\Entity\Group;

/**
 * @extends ServiceEntityRepository<Group>
 */
class GroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Group::class);
    }

    /**
     * @return Group[] Returns an array of Group objects
     */
    public function findAllWithPermissions(): array
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.permissions', 'p')
            ->addSelect('p')
            ->leftJoin('p.resource', 'r')
            ->addSelect('r')
            ->orderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Group[] Returns an array of Group objects with user count
     */
    public function findAllWithUserCount(): array
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.users', 'u')
            ->addSelect('COUNT(u.id) as user_count')
            ->groupBy('g.id')
            ->orderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByName(string $name): ?Group
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.name = :name')
            ->setParameter('name', strtoupper($name))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve ou crée le groupe ADMIN
     */
    public function findOrCreateAdminGroup(): Group
    {
        $adminGroup = $this->findByName('ADMIN');

        if (!$adminGroup) {
            $adminGroup = new Group();
            $adminGroup->setName('ADMIN');
            $adminGroup->setDisplayName('Administrateurs');
            $adminGroup->setDescription('Groupe système avec tous les droits');
            $adminGroup->setIsSystem(true);

            $this->getEntityManager()->persist($adminGroup);
            $this->getEntityManager()->flush();
        }

        return $adminGroup;
    }
}
