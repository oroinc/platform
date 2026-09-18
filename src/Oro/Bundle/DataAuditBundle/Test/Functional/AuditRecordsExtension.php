<?php

namespace Oro\Bundle\DataAuditBundle\Test\Functional;

use Oro\Bundle\DataAuditBundle\Entity\AbstractAudit;
use Oro\Bundle\DataAuditBundle\Entity\AuditField;

/**
 * Reads the audit records created after a given point, so an assertion can show which records
 * appeared, not only how many.
 *
 * Use this trait in a class that has the "getDataFixturesExecutorEntityManager" method.
 */
trait AuditRecordsExtension
{
    protected function getLastAuditId(): int
    {
        return $this->getLastRecordId(AbstractAudit::class);
    }

    protected function getLastAuditFieldId(): int
    {
        return $this->getLastRecordId(AuditField::class);
    }

    /**
     * Groups the records, so an operation on many entities is reported in a few lines.
     *
     * @return array<string, int> "<action> <entity class>" => number of audit records
     */
    protected function getAuditsCreatedAfter(int $lastAuditId): array
    {
        $rows = $this->getDataFixturesExecutorEntityManager()
            ->createQueryBuilder()
            ->select('audit.action AS action', 'audit.objectClass AS objectClass', 'COUNT(audit.id) AS recordCount')
            ->from(AbstractAudit::class, 'audit')
            ->where('audit.id > :lastAuditId')
            ->setParameter('lastAuditId', $lastAuditId)
            ->groupBy('audit.action', 'audit.objectClass')
            ->orderBy('audit.objectClass')
            ->getQuery()
            ->getArrayResult();

        $auditsByEntity = [];
        foreach ($rows as $row) {
            $auditsByEntity[sprintf('%s %s', $row['action'], $row['objectClass'])] = (int)$row['recordCount'];
        }

        return $auditsByEntity;
    }

    /**
     * @return array<string, int> "<entity class>::<field>" => number of audit field records
     */
    protected function getAuditFieldsCreatedAfter(int $lastAuditFieldId): array
    {
        $rows = $this->getDataFixturesExecutorEntityManager()
            ->createQueryBuilder()
            ->select(
                'audit.objectClass AS objectClass',
                'auditField.field AS field',
                'COUNT(auditField.id) AS recordCount'
            )
            ->from(AuditField::class, 'auditField')
            ->innerJoin('auditField.audit', 'audit')
            ->where('auditField.id > :lastAuditFieldId')
            ->setParameter('lastAuditFieldId', $lastAuditFieldId)
            ->groupBy('audit.objectClass', 'auditField.field')
            ->orderBy('audit.objectClass')
            ->getQuery()
            ->getArrayResult();

        $fieldsByEntity = [];
        foreach ($rows as $row) {
            $fieldsByEntity[sprintf('%s::%s', $row['objectClass'], $row['field'])] = (int)$row['recordCount'];
        }

        return $fieldsByEntity;
    }

    private function getLastRecordId(string $entityClass): int
    {
        return (int)$this->getDataFixturesExecutorEntityManager()
            ->createQueryBuilder()
            ->select('MAX(entity.id)')
            ->from($entityClass, 'entity')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
