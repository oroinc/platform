<?php

namespace Oro\Bundle\WorkflowBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;

class ProcessJobRepository extends EntityRepository
{
    const DELETE_HASH_BATCH = 100;

    /**
     * @param ProcessJob $processJob
     * @return null|object
     */
    public function findEntity(ProcessJob $processJob)
    {
        $trigger = $processJob->getProcessTrigger();
        if (!$trigger) {
            return null;
        }

        $definition = $trigger->getDefinition();
        if (!$definition) {
            return null;
        }

        $entityClass = $definition->getRelatedEntity();

        return $this->getEntityManager()->getRepository($entityClass)->find($processJob->getEntityId());
    }

    /**
     * @param array $hashes
     */
    public function deleteByHashes(array $hashes)
    {
        $hashChunks = array_chunk($hashes, self::DELETE_HASH_BATCH);
        foreach ($hashChunks as $hashChunk) {
            $queryBuilder = $this->createQueryBuilder('job')
                ->delete('OroWorkflowBundle:ProcessJob', 'job');
            $queryBuilder->where($queryBuilder->expr()->in('job.entityHash', $hashChunk))
                ->getQuery()
                ->execute();
        }
    }
}
