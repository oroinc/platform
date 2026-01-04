<?php

namespace Oro\Bundle\WorkflowBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;

/**
 * Repository for ProcessJob entity
 */
class ProcessJobRepository extends EntityRepository
{
    public const DELETE_HASH_BATCH = 100;

    public function deleteByHashes(array $hashes)
    {
        $queryBuilder = $this->createQueryBuilder('job');
        $queryBuilder->delete(ProcessJob::class, 'job')
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
