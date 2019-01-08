<?php

namespace Oro\Bundle\DataAuditBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Oro\Bundle\DataAuditBundle\Entity\AuditField;

/**
 * Repository class for AuditField entity
 */
class AuditFieldRepository extends EntityRepository
{
    /**
     * @param array $ids
     * @return AuditField[][]
     */
    public function getVisibleFieldsByAuditIds(array $ids): array
    {
        $ids = array_filter($ids);
        if (!$ids) {
            return [];
        }

        sort($ids);

        $qb = $this->createQueryBuilder('f');
        $qb
            ->select('IDENTITY(f.audit) as audit', 'f as field')
            ->where($qb->expr()->in('IDENTITY(f.audit)', ':ids'))
            ->andWhere($qb->expr()->eq('f.visible', $qb->expr()->literal(true)))
            ->setParameter('ids', $ids);

        $fields = $qb
            ->getQuery()
            ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
            ->getResult();

        $fieldsByAudits = [];
        foreach ($fields as $field) {
            $fieldsByAudits[$field['audit']][] = $field['field'];
        }

        return $fieldsByAudits;
    }
}
