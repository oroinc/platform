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
        $auditFields = $this->getEntityManager()->getRepository(AuditField::class)->findAll();
        self::assertCount(
            $expected,
            $this->getEntityManager()->getRepository(Audit::class)->findAll(),
            sprintf(
                'Failed asserting that there are %d audit records. Changed fields: %s',
                $expected,
                json_encode($auditFields, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES, 2)
            )
        );
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

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->getClientInstance()->getContainer()->get('doctrine.orm.entity_manager');
    }

    /**
     * @return KernelBrowser
     */
    abstract protected static function getClientInstance();
}
