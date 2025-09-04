<?php

namespace Oro\Bundle\ImportExportBundle\Entity\Repository;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;

/**
 * Doctrine repository for ImportExportResult entity
 */
class ImportExportResultRepository extends EntityRepository
{
    public function updateExpiredRecords(\DateTime $from, \DateTime $to): int
    {
        $qb = $this->createQueryBuilder('importExportResult');
        $qb->update()
            ->set('importExportResult.expired', ':expired')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->gte('importExportResult.createdAt', ':from'),
                    $qb->expr()->lte('importExportResult.createdAt', ':to'),
                    $qb->expr()->eq('importExportResult.expired', ':isExpired')
                )
            )
            ->setParameter('expired', true)
            ->setParameter('isExpired', false)
            ->setParameter('from', $from, Types::DATETIME_MUTABLE)
            ->setParameter('to', $to, Types::DATETIME_MUTABLE);

        return (int) $qb->getQuery()->execute();
    }
}
