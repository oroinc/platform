<?php

namespace Oro\Bundle\WorkflowBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;

class ProcessJobRepository extends EntityRepository
{
    /**
     * @param ProcessJob $processJob
     * @return null|object
     */
    public function findEntity(ProcessJob $processJob)
    {
        if ($entityClass = $processJob->getProcessTrigger()->getDefinition()->getRelatedEntity()) {
            return $this->getEntityManager()->getRepository($entityClass)->find($processJob->getEntityId());
        }
        return null;
    }
}
