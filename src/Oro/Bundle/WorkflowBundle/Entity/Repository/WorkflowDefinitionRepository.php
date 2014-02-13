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
     * @param string|null $workflowName
     * @param bool|null $enabled
     * @return WorkflowDefinition[]
     */
    public function findByEntityClass($entityClass, $workflowName = null, $enabled = null)
    {
        $queryBuilder = $this->createQueryBuilder('wd');
        $queryBuilder
            ->where('wd.relatedEntity = :relatedEntity')
            ->setParameter('relatedEntity', $entityClass);

        if ($workflowName) {
            $queryBuilder->andWhere('wd.name = :workflowName')
                ->setParameter('workflowName', $workflowName);
        }

        if (null !== $enabled) {
            $queryBuilder->andWhere('wd.enabled = :enabled')
                ->setParameter('enabled', $enabled);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @return WorkflowDefinition[]
     */
    public function findAllWithStartStep()
    {
        $queryBuilder = $this->createQueryBuilder('wd');
        $queryBuilder
            ->where('wd.startStep IS NOT NULL');

        return $queryBuilder->getQuery()->getResult();
    }
}
