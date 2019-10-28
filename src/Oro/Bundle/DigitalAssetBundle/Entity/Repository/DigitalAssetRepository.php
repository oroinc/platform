<?php

namespace Oro\Bundle\DigitalAssetBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\AttachmentBundle\Entity\File;

/**
 * DigitalAsset entity repository
 */
class DigitalAssetRepository extends EntityRepository
{
    /**
     * @param int $id
     *
     * @return File[]
     */
    public function findChildFilesByDigitalAssetId(int $id): array
    {
        $qb = $this->getEntityManager()
            ->createQueryBuilder();

        $qb
            ->select('file')
            ->from(File::class, 'file')
            ->andWhere($qb->expr()->eq('file.digitalAsset', ':id'))
            ->setParameter('id', $id);

        return $qb->getQuery()->getResult();
    }
}
