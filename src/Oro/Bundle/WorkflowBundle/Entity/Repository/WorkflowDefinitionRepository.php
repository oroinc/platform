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
     * @return WorkflowDefinition
     */
    public function findByEntityClass($entityClass)
    {
        $queryBuilder = $this->createQueryBuilder('wd');
        //TODO: replace with real attribute name in task BAP-2888
        $queryBuilder
            ->where('wd.relatedEntity = :relatedEntity')
            ->setParameter('relatedEntity', $entityClass);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
