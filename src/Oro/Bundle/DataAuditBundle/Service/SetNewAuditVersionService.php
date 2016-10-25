<?php
namespace Oro\Bundle\DataAuditBundle\Service;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DataAuditBundle\Entity\AbstractAudit;

class SetNewAuditVersionService
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param AbstractAudit $audit
     *
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Exception
     *
     * @return void
     */
    public function setVersion(AbstractAudit $audit)
    {
        if (false == $audit->getId()) {
            throw new \InvalidArgumentException('The audit must be already stored.');
        }

        if ($audit->getVersion()) {
            throw new \InvalidArgumentException(sprintf(
                'Audit version already set. Audit: %s, Version: %s.',
                $audit->getId(),
                $audit->getVersion()
            ));
        }

        // here we are going to set next audit version
        // 1. First step is to find closest last version
        // 2. Inside loop set next available version.
        // we use loop here because other processes can do the same job right at the same time

        $qb = $this->entityManager->createQueryBuilder();
        $closestLastVersion = (int) $qb
            ->select('MAX(a.version)')
            ->from(ClassUtils::getClass($audit), 'a')
            ->andWhere('a.objectId = :objectId')
            ->andWhere('a.objectClass = :objectClass')
            ->setParameter('objectId', $audit->getObjectId())
            ->setParameter('objectClass', $audit->getObjectClass())
            ->getQuery()->getSingleScalarResult()
        ;

        for ($i = 0; $i < 100; $i++) {
            if ($this->doSetVersion($audit, ++$closestLastVersion)) {
                $this->entityManager->refresh($audit);

                return;
            }
        }

        throw new \LogicException('Version was not set for audit: id:"%s".', $audit->getId());
    }

    /**
     * @param AbstractAudit $audit
     * @param int $version
     *
     * @return bool
     */
    private function doSetVersion(AbstractAudit $audit, $version)
    {
        $entityClass = ClassUtils::getClass($audit);
        $classMetadata = $this->entityManager->getClassMetadata($entityClass);

        $sql = sprintf(
            'UPDATE %s SET version=:version WHERE id=:auditId AND NOT EXISTS
              (SELECT id FROM
                (SELECT id FROM %s WHERE object_id=:objectId AND object_class=:objectClass AND version=:version)
              as x)',
            $classMetadata->getTableName(),
            $classMetadata->getTableName()
        );

        $affectedRows = $this->entityManager->getConnection()->executeUpdate(
            $sql,
            [
                'auditId' => $audit->getId(),
                'objectId' => $audit->getObjectId(),
                'objectClass' => $audit->getObjectClass(),
                'version' => $version,
            ],
            [
                'auditId' => Type::INTEGER,
                'objectId' => Type::INTEGER,
                'objectClass' => Type::INTEGER,
                'version' => Type::INTEGER,
            ]
        );

        if ($affectedRows > 1) {
            throw new \LogicException('More than one record were update. auditId: "%s".', $audit->getId());
        }

        return $affectedRows === 1;
    }
}
