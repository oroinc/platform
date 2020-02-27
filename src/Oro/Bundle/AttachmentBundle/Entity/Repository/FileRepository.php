<?php

namespace Oro\Bundle\AttachmentBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * File entity repository
 */
class FileRepository extends EntityRepository
{
    /**
     * @param array|int[] $fileIds
     */
    public function deleteByFileIds(array $fileIds)
    {
        // Do not accept empty array
        if (!$fileIds) {
            return;
        }

        $qb = $this->createQueryBuilder('file');

        $qb->delete()
            ->where($qb->expr()->in('file.id', ':fileIds'))
            ->setParameter('fileIds', $fileIds);
        $qb->getQuery()->execute();
    }
}
