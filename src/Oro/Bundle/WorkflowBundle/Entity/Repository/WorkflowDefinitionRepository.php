<?php

namespace Oro\Bundle\WorkflowBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class WorkflowDefinitionRepository extends EntityRepository
{
    /**
     * @return array|WorkflowDefinition[]
     */
    public function getActiveWorkflowDefinitions()
    {
        return $this->findBy(['active' => true]);
    }
}
