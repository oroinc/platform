<?php

namespace Oro\Bundle\TagBundle\Security;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SearchBundle\Security\SecurityProvider as SearchSecurityProvider;

class SecurityProvider
{
    const ENTITY_PERMISSION = 'VIEW';

    /**
     * @var SearchSecurityProvider
     */
    protected $securityProvider;

    /**
     * @param SearchSecurityProvider $securityProvider
     */
    public function __construct(SearchSecurityProvider $securityProvider)
    {
        $this->securityProvider = $securityProvider;
    }

    /**
     * Apply ACL restriction to query builder.
     *
     * @param QueryBuilder $queryBuilder
     * @param string $tableAlias
     */
    public function applyAcl(QueryBuilder $queryBuilder, $tableAlias)
    {
        $allowedEntities = $this->getAllowedEntities($queryBuilder->getEntityManager());
        if ($allowedEntities) {
            $queryBuilder->andWhere($tableAlias . '.entityName IN(:allowedEntities)')
                ->setParameter('allowedEntities', $allowedEntities);
        } else {
            // Do not sho any result if all entities are prohibited
            $queryBuilder->andWhere('1 = 0');
        }
    }

    /**
     * Get allowed to display entities list.
     *
     * @param EntityManager $em
     * @return array
     */
    protected function getAllowedEntities(EntityManager $em)
    {
        $qb = $em->createQueryBuilder()
            ->from('OroTagBundle:Tagging', 't')
            ->select('t.entityName')
            ->distinct(true);

        $allowedEntities = array();
        foreach ($qb->getQuery()->getArrayResult() as $entityClass) {
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
}
