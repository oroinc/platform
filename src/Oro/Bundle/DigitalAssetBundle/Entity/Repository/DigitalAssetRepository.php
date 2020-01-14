<?php

namespace Oro\Bundle\DigitalAssetBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

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

    /**
     * Find files created from digital asset for specific entity field
     *
     * @param string $entityClass
     * @param int|string $entityId
     * @param string $fieldName
     *
     * @return File[]
     */
    public function findForEntityField(
        string $entityClass,
        $entityId,
        string $fieldName
    ): array {
        $qb = $this->getEntityManager()
            ->createQueryBuilder();

        $qb
            ->select('file, digitalAsset, sourceFile')
            ->from(File::class, 'file')
            ->innerJoin('file.digitalAsset', 'digitalAsset')
            ->innerJoin('digitalAsset.sourceFile', 'sourceFile')
            ->where(
                $qb->expr()->eq('file.parentEntityClass', ':parentEntityClass'),
                $qb->expr()->eq('file.parentEntityId', ':parentEntityId'),
                $qb->expr()->eq('file.parentEntityFieldName', ':parentEntityFieldName')
            )
            ->setParameters([
                ':parentEntityClass' => $entityClass,
                ':parentEntityId' => $entityId,
                ':parentEntityFieldName' => $fieldName,
            ]);

        return $qb->getQuery()->getResult();
    }

    /**
     * Find user allowed digital assets by ids
     *
     * @param int[] $ids
     * @param AclHelper $aclHelper
     *
     * @return DigitalAsset[]
     */
    public function findByIds(array $ids, AclHelper $aclHelper): iterable
    {
        $qb = $this->createQueryBuilder('digitalAsset', 'digitalAsset.id');

        $qb->select('digitalAsset, sourceFile')
            ->innerJoin('digitalAsset.sourceFile', 'sourceFile')
            ->where($qb->expr()->in('digitalAsset.id', ':ids'))
            ->setParameter(':ids', $ids);

        return $aclHelper->apply($qb)->getResult();
    }
}
