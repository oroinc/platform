<?php

namespace Oro\Bundle\WorkflowBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class WorkflowDefinitionRepository extends EntityRepository
{
    /**
     * @param string $relatedEntity
     * @return WorkflowDefinition[]
     */
    public function findActiveForRelatedEntity($relatedEntity)
    {
        $criteria = [
            'relatedEntity' => $relatedEntity,
            'active' => true,
        ];

        return $this->findBy($criteria, ['priority' => 'DESC']);
    }
}
