<?php

namespace Oro\Bundle\AttachmentBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\AttachmentBundle\Entity\File;

/**
 * Doctrine repository for File entity.
 */
class FileRepository extends EntityRepository
{
    /**
     * Finds files for a specific entity or entity field.
     *
     * @return File[]
     */
    public function findForEntityField(string $entityClass, int $entityId, ?string $fieldName = null): array
    {
        $qb = $this->createQueryBuilder('file')
            ->where('file.parentEntityClass = :parentEntityClass')
            ->andWhere('file.parentEntityId = :parentEntityId')
            ->setParameter('parentEntityClass', $entityClass)
            ->setParameter('parentEntityId', $entityId);
        if ($fieldName) {
            $qb
                ->andWhere('file.parentEntityFieldName = :parentEntityFieldName')
                ->setParameter(':parentEntityFieldName', $fieldName);
        }

        return $qb->getQuery()->getResult();
    }
}
