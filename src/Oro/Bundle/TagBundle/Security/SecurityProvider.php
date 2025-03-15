<?php

namespace Oro\Bundle\TagBundle\Security;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SearchBundle\Security\SecurityProvider as SearchSecurityProvider;
use Oro\Bundle\TagBundle\Entity\Tagging;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Protects taggable entities by ACL.
 */
class SecurityProvider
{
    private const string ENTITY_PERMISSION = 'VIEW';

    public function __construct(
        private SearchSecurityProvider $securityProvider
    ) {
    }

    /**
     * Applies ACL restriction to query builder.
     */
    public function applyAcl(QueryBuilder $queryBuilder, string $tableAlias): void
    {
        $taggableEntities = $this->getTaggableEntities($queryBuilder->getEntityManager());
        $allowedEntities = $this->getAllowedEntities($taggableEntities);

        if (\count($allowedEntities) !== \count($taggableEntities)) {
            if ($allowedEntities) {
                $queryBuilder
                    ->andWhere(
                        $queryBuilder->expr()
                            ->in(QueryBuilderUtil::getField($tableAlias, 'entityName'), ':allowedEntities')
                    )
                    ->setParameter('allowedEntities', $allowedEntities);
            } else {
                // Do not show any result if all entities are prohibited
                $queryBuilder->andWhere('1 = 0');
            }
        }
    }

    /**
     * Gets allowed to display entities list.
     */
    private function getAllowedEntities(array $taggableEntities): array
    {
        $allowedEntities = [];
        foreach ($taggableEntities as $entityClass) {
            $entityClass = $entityClass['entityName'];
            $objectString = 'Entity:' . $entityClass;

            if ($this->securityProvider->isProtectedEntity($entityClass)) {
                if ($this->securityProvider->isGranted(self::ENTITY_PERMISSION, $objectString)) {
                    $allowedEntities[] = $entityClass;
                }
            } else {
                $allowedEntities[] = $entityClass;
            }
        }

        return $allowedEntities;
    }

    private function getTaggableEntities(EntityManagerInterface $em): array
    {
        return $em->createQueryBuilder()
            ->from(Tagging::class, 't')
            ->select('t.entityName')
            ->distinct()
            ->getQuery()
            ->getArrayResult();
    }
}
