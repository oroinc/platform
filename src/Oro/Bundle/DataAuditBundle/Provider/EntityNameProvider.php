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
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var EntityNameResolver */
    private $entityNameResolver;

    public function __construct(ManagerRegistry $doctrine, EntityNameResolver $entityNameResolver)
    {
        $this->doctrine = $doctrine;
        $this->entityNameResolver = $entityNameResolver;
    }

    /**
     * Gets a human-readable representation of the entity.
     *
     * @param string $auditEntryClass The class name of the audit entity
     * @param string $entityClass     The class name of the audited entity
     * @param int    $entityId        The identifier of the audited entity
     *
     * @return string
     */
    public function getEntityName($auditEntryClass, $entityClass, $entityId)
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

    /**
     * @param string $auditEntryClass
     * @param string $entityClass
     * @param int    $entityId
     *
     * @return string|null
     */
    private function findObjectNameFromLastAuditEntry($auditEntryClass, $entityClass, $entityId)
    {
        $rows = $this->getAuditRepository($auditEntryClass)
            ->createQueryBuilder('a')
            ->select('a.objectName')
            ->where('a.objectClass = :objectClass AND a.objectId = :objectId AND a.version IS NOT NULL')
            ->orderBy('a.version', 'DESC')
            ->setMaxResults(1)
            ->setParameter('objectClass', $entityClass)
            ->setParameter('objectId', (string) $entityId)
            ->getQuery()
            ->getArrayResult();

        return !empty($rows)
            ? $rows[0]['objectName']
            : null;
    }

    /**
     * @param string $auditEntryClass
     *
     * @return EntityRepository
     */
    private function getAuditRepository($auditEntryClass)
    {
        return $this->doctrine->getRepository($auditEntryClass);
    }
}
