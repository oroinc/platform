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
     * @return File[]
     */
    public function findChildFilesByDigitalAssetId(int $id): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('file')
            ->from(File::class, 'file')
            ->andWhere('file.digitalAsset = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult();
    }

    /**
     * Finds source file by digital asset ID.
     */
    public function findSourceFile(int $id): ?File
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('file')
            ->from(File::class, 'file')
            ->innerJoin(DigitalAsset::class, 'digitalAsset', Join::WITH, 'digitalAsset.sourceFile = file')
            ->where('digitalAsset.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Finds files created from digital asset for specific entity field.
     *
     * @return File[]
     */
    public function findForEntityField(string $entityClass, int $entityId, string $fieldName): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('file, digitalAsset, sourceFile')
            ->from(File::class, 'file')
            ->innerJoin('file.digitalAsset', 'digitalAsset')
            ->innerJoin('digitalAsset.sourceFile', 'sourceFile')
            ->where('file.parentEntityClass = :parentEntityClass')
            ->andWhere('file.parentEntityId = :parentEntityId')
            ->andWhere('file.parentEntityFieldName = :parentEntityFieldName')
            ->setParameter('parentEntityClass', $entityClass)
            ->setParameter('parentEntityId', $entityId)
            ->setParameter('parentEntityFieldName', $fieldName)
            ->getQuery()
            ->getResult();
    }

    /**
     * Finds user allowed digital assets by IDs.
     *
     * @param int[]     $ids
     * @param AclHelper $aclHelper
     *
     * @return DigitalAsset[]
     */
    public function findByIds(array $ids, AclHelper $aclHelper): iterable
    {
        $qb = $this->createQueryBuilder('digitalAsset', 'digitalAsset.id')
            ->select('digitalAsset, sourceFile')
            ->innerJoin('digitalAsset.sourceFile', 'sourceFile')
            ->where('digitalAsset.id IN (:ids)')
            ->setParameter(':ids', $ids);

        return $aclHelper->apply($qb)->getResult();
    }

    public function getFileDataForTwigTag(int $fileId): array
    {
        $result = $this->getEntityManager()->createQueryBuilder()
            ->select([
                'file.uuid',
                'file.parentEntityClass',
                'file.parentEntityId',
                'file.parentEntityFieldName',
                'IDENTITY(file.digitalAsset) as digitalAssetId',
                'file.extension',
            ])
            ->from(File::class, 'file')
            ->where('file.id = :fileId')
            ->setParameter('fileId', $fileId)
            ->getQuery()
            ->getArrayResult();

        return $result ? reset($result) : [];
    }
}
