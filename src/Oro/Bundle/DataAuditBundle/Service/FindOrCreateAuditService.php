<?php
namespace Oro\Bundle\DataAuditBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DataAuditBundle\Entity\AbstractAudit;
use Oro\Bundle\DataAuditBundle\Loggable\AuditEntityMapper;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

class FindOrCreateAuditService
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var AuditEntityMapper
     */
    private $auditEntityMapper;

    /**
     * @param EntityManagerInterface $entityManager
     * @param AuditEntityMapper $auditEntityMapper
     */
    public function __construct(EntityManagerInterface $entityManager, AuditEntityMapper $auditEntityMapper)
    {
        $this->entityManager = $entityManager;
        $this->auditEntityMapper = $auditEntityMapper;
    }

    /**
     * @param AbstractUser|null $user
     * @param string $objectClass
     * @param string $objectId
     * @param string $transactionId
     *
     * @return AbstractAudit
     */
    public function findOrCreate($user, $objectClass, $objectId, $transactionId)
    {
        $auditClass = $this->auditEntityMapper->getAuditEntryClass($user);

        $query = $this->entityManager
            ->createQueryBuilder()
            ->select('log')
            ->from($auditClass, 'log')
            ->andWhere('log.objectId = :objectId')
            ->andWhere('log.objectClass = :objectClass')
            ->andWhere('log.transactionId = :transactionId')
            ->setParameter('objectId', $objectId)
            ->setParameter('objectClass', $objectClass)
            ->setParameter('transactionId', $transactionId)
            ->getQuery()
        ;

        return $query->getOneOrNullResult() ?: $this->createAudit($user, $objectId, $objectClass, $transactionId);
    }

    /**
     * @param AbstractUser|null $user
     * @param string $objectId
     * @param string $objectClass
     * @param string $transactionId
     *
     * @return AbstractAudit
     */
    private function createAudit($user, $objectId, $objectClass, $transactionId)
    {
        $auditClass = $this->auditEntityMapper->getAuditEntryClass($user);

        $auditMeta = $this->entityManager->getClassMetadata($auditClass);

        /** @var AbstractAudit $audit */
        $audit = $auditMeta->newInstance();
        $audit->setUser($user);
        $audit->setObjectId($objectId);
        $audit->setObjectClass($objectClass);
        $audit->setTransactionId($transactionId);

        $this->entityManager->persist($audit);
        $this->entityManager->flush();

        return $audit;
    }
}
