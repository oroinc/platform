<?php

namespace Oro\Bundle\WorkflowBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;

class ProcessJobRepository extends EntityRepository
{
    const DELETE_HASH_BATCH = 100;

    /**
     * @param array $hashes
     */
    public function deleteByHashes(array $hashes)
    {
        $queryBuilder = $this->createQueryBuilder('job');
        $queryBuilder->delete('OroWorkflowBundle:ProcessJob', 'job')
            ->where($queryBuilder->expr()->in('job.entityHash', ':hashChunk'));

        $hashChunks = array_chunk($hashes, self::DELETE_HASH_BATCH);
        foreach ($hashChunks as $hashChunk) {
            $queryBuilder->setParameter('hashChunk', $hashChunk)
                ->getQuery()
                ->execute();
        }
    }

    /**
     * @param array $ids
     * @return array
     */
    public function findByIds(array $ids)
    {
        if (empty($ids)) {
            return array();
        } else {
            return $this->findBy(['id' => $ids]);
        }
    }
}
