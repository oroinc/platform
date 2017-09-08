<?php
namespace Oro\Bundle\DataAuditBundle\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DataAuditBundle\Async\AuditChangedEntitiesProcessor;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataChild;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataOwner;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Symfony\Bundle\FrameworkBundle\Client;

trait AuditChangedEntitiesExtensionTrait
{
    /**
     * @return TestAuditDataOwner
     */
    protected function createOwner()
    {
        $owner = new TestAuditDataOwner();

        $this->getEntityManager()->persist($owner);
        $this->getEntityManager()->flush();

        return $owner;
    }

    /**
     * @return TestAuditDataChild
     */
    protected function createChild()
    {
        $child = new TestAuditDataChild();

        $this->getEntityManager()->persist($child);
        $this->getEntityManager()->flush();

        return $child;
    }
    
    private function assertStoredAuditCount($expected)
    {
        $this->assertCount($expected, $this->getEntityManager()->getRepository(Audit::class)->findAll());
    }

    /**
     * @return Audit
     */
    private function findLastStoredAudit()
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('log')
            ->from(Audit::class, 'log')
            ->orderBy('log.id', 'DESC')
            ->setMaxResults(1)
        ;

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * @return Audit[]
     */
    private function findStoredAudits()
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('log')
            ->from(Audit::class, 'log')
            ->orderBy('log.id', 'DESC')
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array $body
     * @return NullMessage
     */
    private function createDummyMessage(array $body)
    {
        $body = array_replace([
            'timestamp' => time(),
            'transaction_id' => 'aTransactionId',
            'entities_inserted' => [],
            'entities_updated' => [],
            'entities_deleted' => [],
            'collections_updated' => [],
        ], $body);


        $message = new NullMessage();
        $message->setBody(json_encode($body));

        return $message;
    }

    /**
     * @return User
     */
    private function findAdmin()
    {
        return $this->getEntityManager()->getRepository(User::class)->findOneBy([
            'username' => 'admin'
        ]);
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getEntityManager()
    {
        return $this->getClient()->getContainer()->get('doctrine.orm.entity_manager');
    }

    /**
     * @return AuditChangedEntitiesProcessor
     */
    protected function getAuditChangedEntitiesProcessor()
    {
        return $this->getClient()->getContainer()->get('oro_dataaudit.async.audit_changed_entities');
    }

    /**
     * @return Client
     */
    abstract protected function getClient();
}
