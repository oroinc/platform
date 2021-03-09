<?php

namespace Oro\Bundle\AttachmentBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\AttachmentBundle\Entity\File;

/**
 * File entity repository
 */
class FileRepository extends EntityRepository
{
    /**
     * Find files for specific entity fields
     *
     * @param string $entityClass
     * @param int $entityId
     * @param null|string $fieldName
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
