<?php

namespace ItechWorld\UserManagementBundle\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

class GlobalSearchFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if ($property !== 'search' || empty($value)) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $parameterName = $queryNameGenerator->generateParameterName('search');

        // Définir les champs dans lesquels rechercher selon l'entité
        $searchableFields = $this->getSearchableFields($resourceClass, $alias);

        if (empty($searchableFields)) {
            return;
        }

        // Construire la condition OR pour tous les champs
        $orConditions = [];
        foreach ($searchableFields as $field) {
            $orConditions[] = sprintf('%s LIKE :%s', $field, $parameterName);
        }

        $queryBuilder
            ->andWhere('(' . implode(' OR ', $orConditions) . ')')
            ->setParameter($parameterName, '%' . $value . '%');
    }

    private function getSearchableFields(string $resourceClass, string $alias): array
    {
        // Configuration des champs selon l'entité
        $fieldsConfig = [
            'App\Entity\User' => [
                $alias . '.username',
                $alias . '.firstName',
                $alias . '.lastName'
            ]
            // On peut ajouter d'autres entités ici
        ];

        return $fieldsConfig[$resourceClass] ?? [];
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'search' => [
                'property' => 'search',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'description' => 'Recherche globale dans plusieurs champs',
                'openapi' => [
                    'description' => 'Recherche globale dans username, firstName et lastName',
                    'name' => 'search',
                    'type' => 'string',
                ],
            ],
        ];
    }
}
