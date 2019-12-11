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

    /**
     * Find files for specific entity fields
     *
     * @param string $entityClass
     * @param int $entityId
     * @param string|null fieldNames
     *
     * @return File[]
     */
    public function findForEntityField(string $entityClass, int $entityId, ?string $fieldName = null): iterable
    {
        $qb = $this->createQueryBuilder('file');

        $qb->select('file')
            ->where(
                $qb->expr()->eq('file.parentEntityClass', ':parentEntityClass'),
                $qb->expr()->eq('file.parentEntityId', ':parentEntityId')
            )
            ->setParameters([
                ':parentEntityClass' => $entityClass,
                ':parentEntityId' => $entityId,
            ]);

        if ($fieldName) {
            $qb
                ->andWhere($qb->expr()->eq('file.parentEntityFieldName', ':parentEntityFieldName'))
                ->setParameter(':parentEntityFieldName', $fieldName);
        }
        return $qb->getQuery()->getResult();
    }
}
