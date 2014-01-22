<?php

namespace Oro\Bundle\WorkflowBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;

class WorkflowStepRepository extends EntityRepository
{
    /**
     * @param string $entityClass
     * @return WorkflowStep[]
     */
    public function findByRelatedEntity($entityClass)
    {
        $entityClass = $this->getEntityManager()->getClassMetadata($entityClass)->getName();

        return $this->getEntityManager()->createQueryBuilder()
            ->select('workflowStep')
            ->from('OroWorkflowBundle:WorkflowStep', 'workflowStep')
            ->join('workflowStep.definition', 'workflowDefinition')
            ->where('workflowDefinition.relatedEntity = :entityClass')
            ->orderBy('workflowStep.stepOrder', 'ASC')
            ->setParameter('entityClass', $entityClass)
            ->getQuery()
            ->getResult();
    }
}
