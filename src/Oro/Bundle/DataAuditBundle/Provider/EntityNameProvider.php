<?php

namespace Oro\Bundle\DataAuditBundle\Provider;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataAuditBundle\Entity\AbstractAudit;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;

/**
 * Get entity name from entity itself or from the latest audit logs
 */
class EntityNameProvider
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly EntityNameResolver $entityNameResolver
    ) {
    }

    /**
     * Gets a human-readable representation of the entity.
     */
    public function getEntityName(string $auditEntryClass, string $entityClass, mixed $entityId): string
    {
        $entity = $this->doctrine->getManagerForClass($entityClass)->find($entityClass, $entityId);
        if ($entity) {
            return mb_substr($this->entityNameResolver->getName($entity), 0, AbstractAudit::OBJECT_NAME_MAX_LENGTH);
        }

        $lastObjectName = $this->findObjectNameFromLastAuditEntry($auditEntryClass, $entityClass, $entityId);
        if ($lastObjectName) {
            return $lastObjectName;
        }

        return sprintf('%s::%s', (new \ReflectionClass($entityClass))->getShortName(), $entityId);
    }

    private function findObjectNameFromLastAuditEntry(
        string $auditEntryClass,
        string $entityClass,
        mixed $entityId
    ): ?string {
        $rows = $this->getAuditRepository($auditEntryClass)
            ->createQueryBuilder('a')
            ->select('a.objectName')
            ->where('a.objectClass = :objectClass AND a.objectId = :objectId AND a.version IS NOT NULL')
            ->orderBy('a.version', 'DESC')
            ->setMaxResults(1)
            ->setParameter('objectClass', $entityClass)
            ->setParameter('objectId', (string)$entityId)
            ->getQuery()
            ->getArrayResult();

        return !empty($rows)
            ? $rows[0]['objectName']
            : null;
    }

    private function getAuditRepository(string $auditEntryClass): EntityRepository
    {
        return $this->doctrine->getRepository($auditEntryClass);
    }
}
