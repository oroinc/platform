<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Functional\Async;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\DataAuditBundle\Entity\AuditField;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataChild;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataOwner;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Transport\Message;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

trait AuditChangedEntitiesExtensionTrait
{
    private function createOwner(): TestAuditDataOwner
    {
        $owner = new TestAuditDataOwner();

        $this->getEntityManager()->persist($owner);
        $this->getEntityManager()->flush();

        return $owner;
    }

    private function createChild(): TestAuditDataChild
    {
        $child = new TestAuditDataChild();

        $this->getEntityManager()->persist($child);
        $this->getEntityManager()->flush();

        return $child;
    }

    private function assertStoredAuditCount($expected): void
    {
        $audits = $this->getEntityManager()->getRepository(Audit::class)->findAll();
        if (\count($audits) !== $expected) {
            self::fail(
                \sprintf('Failed asserting that there are %d audit records. Changed fields:', $expected)
                . "\n"
                . implode("\n", array_map(function (AuditField $auditField) {
                    return \sprintf(
                        '{id: %d, class: %s, field: %s, old value: %s, new value: %s}',
                        $auditField->getId(),
                        $auditField->getAudit()->getObjectClass(),
                        $auditField->getField(),
                        json_encode($auditField->getOldValue(), JSON_THROW_ON_ERROR, 2),
                        json_encode($auditField->getNewValue(), JSON_THROW_ON_ERROR, 2)
                    );
                }, $this->getEntityManager()->getRepository(AuditField::class)->findAll()))
            );
        }
    }

    private function findLastStoredAudit(): Audit
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('log')
            ->from(Audit::class, 'log')
            ->orderBy('log.id', 'DESC')
            ->setMaxResults(1);

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * @return Audit[]
     */
    private function findStoredAudits(): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('log')
            ->from(Audit::class, 'log')
            ->orderBy('log.id', 'DESC');

        return $qb->getQuery()->getResult();
    }

    private function createDummyMessage(array $body): Message
    {
        $body = array_replace([
            'timestamp' => time(),
            'transaction_id' => 'aTransactionId',
            'entities_inserted' => [],
            'entities_updated' => [],
            'entities_deleted' => [],
            'collections_updated' => [],
        ], $body);

        $message = new Message();
        $message->setBody($body);
        $message->setMessageId('some_message_id');

        return $message;
    }

    private function findAdmin(): User
    {
        return $this->getEntityManager()->getRepository(User::class)->findOneBy([
            'username' => 'admin'
        ]);
    }

    private function getEntityManager(?string $entityClass = null): EntityManagerInterface
    {
        $doctrine = self::getContainer()->get('doctrine');
        if ($entityClass) {
            return $doctrine->getManagerForClass($entityClass);
        }

        return $doctrine->getManager();
    }

    /**
     * @return KernelBrowser
     */
    abstract protected static function getClientInstance();
}
