<?php
namespace Oro\Bundle\DataAuditBundle\Service;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DataAuditBundle\Entity\AbstractAudit;

class SetNewAuditVersionService
{
    const MAX_ATTEMPTS_LIMIT = 100;

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

        $tableName = $this->entityManager->getClassMetadata(ClassUtils::getClass($audit))->getTableName();
        $statement = $this->entityManager->getConnection()->prepare(
            "UPDATE $tableName SET version = (
                SELECT v FROM (
                    SELECT COALESCE(MAX(version) + 1, 1) AS v FROM $tableName
                    WHERE object_id = :objectId AND object_class = :objectClass
                ) AS x
            ) WHERE id = :auditId"
        );
        $statement->bindValue('objectId', $audit->getObjectId(), Type::INTEGER);
        $statement->bindValue('objectClass', $audit->getObjectClass(), Type::STRING);
        $statement->bindValue('auditId', $audit->getId(), Type::INTEGER);

        $attempt = 1;
        do {
            $fail = false;
            try {
                $statement->execute();
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt > self::MAX_ATTEMPTS_LIMIT) {
                    throw $e;
                }
                $attempt++;
                $fail = true;
            }
        } while ($fail);

        $this->entityManager->refresh($audit);
    }
}
