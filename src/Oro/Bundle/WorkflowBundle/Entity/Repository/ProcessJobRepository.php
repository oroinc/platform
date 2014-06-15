<?php

namespace Oro\Bundle\WorkflowBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;

class ProcessJobRepository extends EntityRepository
{
    public function findEntity(ProcessJob $processJob)
    {
        $entityHash  = $processJob->getEntityHash();
        $entityId    = $processJob->getEntityId();
        $entityClass = substr($entityHash, 0, strrpos($entityHash, $entityId));

        $entityManager = $this->getEntityManager();
        return $entityManager->getRepository($entityClass)->find($entityId);
    }
}
