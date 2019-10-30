<?php

namespace Oro\Bundle\DigitalAssetBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;

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

    /**
     * Find source file by digital asset id
     *
     * @param int $id
     * @return File
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findSourceFile(int $id): File
    {
        $qb = $this->getEntityManager()
            ->createQueryBuilder();

        $qb->select('file')
            ->from(File::class, 'file')
            ->innerJoin(DigitalAsset::class, 'digitalAsset', Join::WITH, 'digitalAsset.sourceFile = file')
            ->where($qb->expr()->eq('digitalAsset.id', ':id'))
            ->setParameter('id', $id);

        return $qb->getQuery()->getSingleResult();
    }
}
