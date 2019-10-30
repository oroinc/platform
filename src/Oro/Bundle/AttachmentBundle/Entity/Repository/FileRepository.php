<?php

namespace Oro\Bundle\AttachmentBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\AttachmentBundle\Entity\File;

/**
 * File entity repository
 */
class FileRepository extends EntityRepository
{
    /**
     * Find all files for an entity that has a file with specified UUID
     *
     * @param string $uuid
     * @return File[]
     */
    public function findAllForEntityByOneUuid(string $uuid): array
    {
        $qb = $this->createQueryBuilder('file', 'file.uuid');
        $qb
            ->innerJoin(
                File::class,
                'fileWithUuid',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('fileWithUuid.parentEntityClass', 'file.parentEntityClass'),
                    $qb->expr()->eq('fileWithUuid.parentEntityId', 'file.parentEntityId')
                )
            )
            ->where($qb->expr()->eq('fileWithUuid.uuid', ':uuid'))
            ->setParameter('uuid', $uuid);

        return $qb->getQuery()->getResult();
    }
}
