<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Functional\Async;

use Oro\Bundle\DataAuditBundle\Async\AuditChangedEntitiesProcessor;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\DataAuditBundle\Entity\AuditField;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataOwner;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\Email;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;

/**
 * @dbIsolationPerTest
 */
class AuditDeletedEntitiesTest extends WebTestCase
{
    use AuditChangedEntitiesExtensionTrait;

    /** @var AuditChangedEntitiesProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->initClient();
        $this->processor = $this->getContainer()->get('oro_dataaudit.async.audit_changed_entities');
    }

    public function testShouldCreateAuditForDeletedEntity()
    {
        $expectedLoggedAt = new \DateTime('2012-02-01 03:02:01+0000');

        $message = $this->createDummyMessage([
            'timestamp' => $expectedLoggedAt->getTimestamp(),
            'transaction_id' => 'theTransactionId',
            'owner_description' => 'Some Owner Description',
            'entities_deleted' => [
                '000000007ec8f22c00000000536823d4' => [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id' => 123,
                    'change_set' => [
                        'stringProperty' => ['anOldValue', null],
                    ],
                ]
            ],
        ]);

        $this->processor->process($message, $this->getConnection()->createSession());

        $this->assertStoredAuditCount(1);
        $audit = $this->findLastStoredAudit();

        $this->assertNotNull($audit->getId());
        $this->assertEquals(123, $audit->getObjectId());
        $this->assertEquals(Audit::ACTION_REMOVE, $audit->getAction());
        $this->assertEquals(TestAuditDataOwner::class, $audit->getObjectClass());
        $this->assertEquals('TestAuditDataOwner::123', $audit->getObjectName());
        $this->assertEquals(1, $audit->getVersion());
        $this->assertEquals('theTransactionId', $audit->getTransactionId());
        $this->assertEquals($expectedLoggedAt, $audit->getLoggedAt());
        $this->assertEquals('Some Owner Description', $audit->getOwnerDescription());
        $this->assertNull($audit->getUser());
        $this->assertNull($audit->getOrganization());

        $this->assertEquals(1, $audit->getFields()->count());

        /** @var AuditField $field */
        $field = $audit->getFields()->first();
        $this->assertEquals(null, $field->getNewValue());
        $this->assertEquals('anOldValue', $field->getOldValue());
    }

    public function testShouldSkipAuditCreationForDeletedNotAuditableEntity()
    {
        $message = $this->createDummyMessage([
            'timestamp' => time(),
            'entities_deleted' => [
                '000000007ec8f22c00000000536823d4' => [
                    'entity_class' => Email::class,
                    'entity_id' => 123,
                    'change_set' => [
                        'stringProperty' => ['anOldValue', null],
                    ],
                ]
            ],
        ]);

        $this->processor->process($message, $this->getConnection()->createSession());

        $this->assertStoredAuditCount(0);
    }

    public function testShouldCreateAuditsForDeletedEntities()
    {
        $message = $this->createDummyMessage([
            'entities_deleted' => [
                '000000007ec8f22c00000000536823d4' => [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id' => 111,
                    'change_set' => [
                        'stringProperty' => ['anOldValue', null],
                    ],
                ],
                '000000007ec8f22f00000000536823d4' => [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id' => 333,
                    'change_set' => [
                        'stringProperty' => ['anOldValue2', null],
                    ],
                ]
            ],
        ]);

        $this->processor->process($message, $this->getConnection()->createSession());

        $this->assertStoredAuditCount(2);
    }

    public function testShouldCreateAuditForDeletedEntityAndSetUserIfPresent()
    {
        $user = $this->findAdmin();

        $message = $this->createDummyMessage([
            'user_id' => $user->getId(),
            'user_class' => User::class,
            'transaction_id' => 'aTransactionId',
            'entities_deleted' => [
                '000000007ec8f22c00000000536823d4' => [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id' => 123,
                    'change_set' => [
                        'stringProperty' => ['anOldValue', null],
                    ],
                ],
            ],
        ]);

        $this->processor->process($message, $this->getConnection()->createSession());

        $this->assertStoredAuditCount(1);
        $audit = $this->findLastStoredAudit();

        $this->assertInstanceOf(User::class, $audit->getUser());
        $this->assertSame($user->getId(), $audit->getUser()->getId());
    }

    public function testShouldCreateAuditForDeletedEntityAndSetOrganizationIfPresent()
    {
        $organization = new Organization();
        $organization->setName('anOrganizationName');
        $organization->setEnabled(true);
        $this->getEntityManager()->persist($organization);
        $this->getEntityManager()->flush();

        $message = $this->createDummyMessage([
            'timestamp' => time(),
            'organization_id' => $organization->getId(),
            'entities_deleted' => [
                '000000007ec8f22c00000000536823d4' => [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id' => 123,
                    'change_set' => [
                        'stringProperty' => ['anOldValue', null],
                    ],
                ],
            ],
        ]);

        $this->processor->process($message, $this->getConnection()->createSession());

        $this->assertStoredAuditCount(1);
        $audit = $this->findLastStoredAudit();

        $this->assertInstanceOf(Organization::class, $audit->getOrganization());
        $this->assertSame($organization->getId(), $audit->getOrganization()->getId());
    }

    private function assertStoredAuditCount(int $expected): void
    {
        $this->assertCount($expected, $this->getEntityManager()->getRepository(Audit::class)->findAll());
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

    private function getConnection(): ConnectionInterface
    {
        return self::getContainer()->get('oro_message_queue.transport.connection');
    }
}
