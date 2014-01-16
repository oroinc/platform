<?php

namespace Oro\Bundle\WorkflowBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class WorkflowDefinitionRepository extends EntityRepository
{
    /**
     * Get available workflow definitions for entity class
     *
     * @param string $entityClass
     * @return WorkflowDefinition|null
     */
    public function findByEntityClass($entityClass)
    {
        $queryBuilder = $this->createQueryBuilder('wd');
        $queryBuilder
            ->where('wd.relatedEntity = :relatedEntity')
            ->setParameter('relatedEntity', $entityClass);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
