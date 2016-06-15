<?php
namespace Oro\Bundle\DataAuditBundle\Service;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\LockMode;
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
            throw new \InvalidArgumentException('The audit must be already stored');
        }
        if ($audit->getVersion()) {
            throw new \InvalidArgumentException(sprintf(
                'Audit version already set. Audit: %s, Version: %s',
                $audit->getId(),
                $audit->getVersion()
            ));
        }

        $this->entityManager->beginTransaction();
        try {
            $qb = $this->entityManager->createQueryBuilder();
            $query = $qb
                ->select('MAX(log.version)')
                ->from(ClassUtils::getClass($audit), 'log')
                ->andWhere('log.objectId = :objectId')
                ->andWhere('log.objectClass = :objectClass')
                ->setParameter('objectId', $audit->getObjectId())
                ->setParameter('objectClass', $audit->getObjectClass())
                ->getQuery();

            $query->setLockMode(LockMode::PESSIMISTIC_WRITE);

            $audit->setVersion((int) $query->getSingleScalarResult() + 1);
            $this->entityManager->persist($audit);

            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();

            throw $e;
        }
    }
}
