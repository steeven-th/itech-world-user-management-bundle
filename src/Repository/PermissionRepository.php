<?php

namespace ItechWorld\UserManagementBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use ItechWorld\UserManagementBundle\Entity\Permission;
use ItechWorld\UserManagementBundle\Entity\Resource;
use ItechWorld\UserManagementBundle\Entity\User;

/**
 * @extends ServiceEntityRepository<Permission>
 */
class PermissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Permission::class);
    }

    /**
     * Trouve une permission par ressource et action
     */
    public function findByResourceAndAction(Resource $resource, string $action): ?Permission
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.resource = :resource')
            ->andWhere('p.action = :action')
            ->setParameter('resource', $resource)
            ->setParameter('action', $action)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve toutes les permissions d'une ressource
     */
    public function findByResource(Resource $resource): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.resource = :resource')
            ->setParameter('resource', $resource)
            ->orderBy('p.action', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les permissions d'un utilisateur
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.users', 'u')
            ->andWhere('u = :user')
            ->setParameter('user', $user)
            ->leftJoin('p.resource', 'r')
            ->addSelect('r')
            ->orderBy('r.name', 'ASC')
            ->addOrderBy('p.action', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si un utilisateur a une permission spécifique
     */
    public function userHasPermission(User $user, string $resourceName, string $action): bool
    {
        $count = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->innerJoin('p.resource', 'r')
            ->innerJoin('p.users', 'u')
            ->andWhere('r.name = :resourceName')
            ->andWhere('p.action = :action')
            ->andWhere('u = :user')
            ->setParameter('resourceName', $resourceName)
            ->setParameter('action', $action)
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Trouve toutes les permissions avec leurs ressources
     */
    public function findAllWithResources(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.resource', 'r')
            ->addSelect('r')
            ->orderBy('r.name', 'ASC')
            ->addOrderBy('p.action', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche de permissions par code ou description
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.resource', 'r')
            ->addSelect('r')
            ->andWhere('r.name LIKE :query OR p.action LIKE :query OR p.description LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('r.name', 'ASC')
            ->addOrderBy('p.action', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
