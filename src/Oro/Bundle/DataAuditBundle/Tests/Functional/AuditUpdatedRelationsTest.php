<?php
namespace Oro\Bundle\DataAuditBundle\Tests\Functional;

use Oro\Bundle\DataAuditBundle\Async\AuditChangedEntitiesRelationsProcessor;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\DataAuditBundle\Entity\AuditField;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataChild;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataOwner;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Transport\Null\NullSession;

/**
 * @dbIsolationPerTest
 */
class AuditUpdatedRelationsTest extends WebTestCase
{
    use AuditChangedEntitiesExtensionTrait;

    protected function setUp()
    {
        $this->initClient();
    }

    public function testShouldNotCreateAuditEntityForUpdatedCollectionWithoutChangesButEntityAuditable()
    {
        $message = $this->createDummyMessage([
            'collections_updated' => [
                [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id' => 123
                ]
            ],
        ]);

        /** @var AuditChangedEntitiesRelationsProcessor $processor */
        $processor = $this->getContainer()->get('oro_dataaudit.async.audit_changed_entities_relations');

        $processor->process($message, new NullSession());

        $this->assertStoredAuditCount(0);
    }

    public function testShouldUsePreviouslyCreatedAuditWithSameTransaction()
    {
        $expectedLoggedAt = new \DateTime('2012-02-01 03:02:01+0000');

        $user = $this->findAdmin();

        $audit = new Audit();
        $audit->setAction(Audit::ACTION_UPDATE);
        $audit->setUser($user);
        $audit->setVersion(10);
        $audit->setObjectId(123);
        $audit->setObjectClass(TestAuditDataOwner::class);
        $audit->setTransactionId('theTransactionId');
        $this->getEntityManager()->persist($audit);
        $this->getEntityManager()->flush();


        $message = $this->createDummyMessage([
            'timestamp' => $expectedLoggedAt->getTimestamp(),
            'transaction_id' => 'theTransactionId',
            'collections_updated' => [
                [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id' => 123,
                    'change_set' => [
                        'childrenManyToMany' => [
                            null,
                            [
                                'inserted' => [
                                    [
                                        'entity_class' => TestAuditDataChild::class,
                                        'entity_id' => 321,
                                    ]
                                ],
                                'deleted' => [],
                                'changed' => [],
                            ]
                        ]
                    ]
                ]
            ],
        ]);

        /** @var AuditChangedEntitiesRelationsProcessor $processor */
        $processor = $this->getContainer()->get('oro_dataaudit.async.audit_changed_entities_relations');

        $processor->process($message, new NullSession());

        $this->assertStoredAuditCount(1);
        $audit = $this->findLastStoredAudit();

        $this->assertNotNull($audit->getId());
        $this->assertEquals(123, $audit->getObjectId());
        $this->assertEquals(Audit::ACTION_UPDATE, $audit->getAction());
        $this->assertEquals(10, $audit->getVersion());
        $this->assertEquals('theTransactionId', $audit->getTransactionId());
    }

    public function testShouldCreateAuditForInsertedEntityToManyToManyCollection()
    {
        $expectedLoggedAt = new \DateTime('2012-02-01 03:02:01+0000');

        $message = $this->createDummyMessage([
            'timestamp' => $expectedLoggedAt->getTimestamp(),
            'transaction_id' => 'theTransactionId',
            'collections_updated' => [
                [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id' => 123,
                    'change_set' => [
                        'childrenManyToMany' => [
                            null,
                            [
                                'inserted' => [
                                    [
                                        'entity_class' => TestAuditDataChild::class,
                                        'entity_id' => 321,
                                    ]
                                ],
                                'deleted' => [],
                                'changed' => [],
                            ]
                        ]
                    ]
                ]
            ],
        ]);

        /** @var AuditChangedEntitiesRelationsProcessor $processor */
        $processor = $this->getContainer()->get('oro_dataaudit.async.audit_changed_entities_relations');

        $processor->process($message, new NullSession());

        $this->assertStoredAuditCount(1);
        $audit = $this->findLastStoredAudit();

        $this->assertNotNull($audit->getId());
        $this->assertEquals(123, $audit->getObjectId());
        $this->assertEquals(Audit::ACTION_CREATE, $audit->getAction());
        $this->assertEquals(TestAuditDataOwner::class, $audit->getObjectClass());
        $this->assertEquals('TestAuditDataOwner::123', $audit->getObjectName());
        $this->assertEquals(1, $audit->getVersion());
        $this->assertEquals('theTransactionId', $audit->getTransactionId());
        $this->assertEquals($expectedLoggedAt, $audit->getLoggedAt());
        $this->assertNull($audit->getUser());
        $this->assertNull($audit->getOrganization());
    }

    public function testShouldCreateAuditForDeletedEntityFromManyToManyCollection()
    {
        $message = $this->createDummyMessage([
            'collections_updated' => [
                [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id' => 123,
                    'change_set' => [
                        'childrenManyToMany' => [
                            null,
                            [
                                'deleted' => [
                                    [
                                        'entity_class' => TestAuditDataChild::class,
                                        'entity_id' => 321,
                                    ]
                                ],
                                'inserted' => [],
                                'changed' => [],
                            ]
                        ]
                    ]
                ]
            ],
        ]);

        /** @var AuditChangedEntitiesRelationsProcessor $processor */
        $processor = $this->getContainer()->get('oro_dataaudit.async.audit_changed_entities_relations');

        $processor->process($message, new NullSession());

        $this->assertStoredAuditCount(1);

        $audit = $this->findLastStoredAudit();
        $this->assertCount(1, $audit->getFields());

        $auditField = $audit->getField('childrenManyToMany');
        $this->assertInstanceOf(AuditField::class, $auditField);

        $this->assertSame($audit, $auditField->getAudit());
        $this->assertSame('text', $auditField->getDataType());
        $this->assertSame('childrenManyToMany', $auditField->getField());
        $this->assertEquals("\nRemoved: TestAuditDataChild::321", $auditField->getNewValue());
        $this->assertEquals(null, $auditField->getOldValue());
    }

    public function testShouldCreateAuditForDeletedAndInsertedEntitiesFromManyToManyCollection()
    {
        $message = $this->createDummyMessage([
            'collections_updated' => [
                [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id' => 123,
                    'change_set' => [
                        'childrenManyToMany' => [
                            null,
                            [
                                'deleted' => [
                                    [
                                        'entity_class' => TestAuditDataChild::class,
                                        'entity_id' => 321,
                                    ]
                                ],
                                'inserted' => [
                                    [
                                        'entity_class' => TestAuditDataChild::class,
                                        'entity_id' => 567,
                                    ]
                                ],
                                'changed' => [],
                            ]
                        ]
                    ]
                ]
            ],
        ]);

        /** @var AuditChangedEntitiesRelationsProcessor $processor */
        $processor = $this->getContainer()->get('oro_dataaudit.async.audit_changed_entities_relations');

        $processor->process($message, new NullSession());

        $this->assertStoredAuditCount(1);

        $audit = $this->findLastStoredAudit();
        $this->assertCount(1, $audit->getFields());

        $auditField = $audit->getField('childrenManyToMany');
        $this->assertInstanceOf(AuditField::class, $auditField);

        $this->assertSame($audit, $auditField->getAudit());
        $this->assertSame('text', $auditField->getDataType());
        $this->assertSame('childrenManyToMany', $auditField->getField());
        $this->assertEquals(
            "Added: TestAuditDataChild::567\nRemoved: TestAuditDataChild::321",
            $auditField->getNewValue()
        );
        $this->assertEquals(null, $auditField->getOldValue());
    }

    public function testShouldCreateAuditForDeletedEntityFromManyToOneCollection()
    {
        $message = $this->createDummyMessage([
            'collections_updated' => [
                [
                    'entity_class' => TestAuditDataChild::class,
                    'entity_id' => 123,
                    'change_set' => [
                        'ownerManyToOne' => [
                            null,
                            [
                                'deleted' => [
                                    [
                                        'entity_class' => TestAuditDataOwner::class,
                                        'entity_id' => 321,
                                    ]
                                ],
                                'inserted' => [],
                                'changed' => [],
                            ]
                        ]
                    ]
                ]
            ],
        ]);

        /** @var AuditChangedEntitiesRelationsProcessor $processor */
        $processor = $this->getContainer()->get('oro_dataaudit.async.audit_changed_entities_relations');

        $processor->process($message, new NullSession());

        $this->assertStoredAuditCount(1);

        $audit = $this->findLastStoredAudit();
        $this->assertCount(1, $audit->getFields());

        $auditField = $audit->getField('ownerManyToOne');
        $this->assertInstanceOf(AuditField::class, $auditField);

        $this->assertSame($audit, $auditField->getAudit());
        $this->assertSame('text', $auditField->getDataType());
        $this->assertSame('ownerManyToOne', $auditField->getField());
        $this->assertEquals("\nRemoved: TestAuditDataOwner::321", $auditField->getNewValue());
        $this->assertEquals(null, $auditField->getOldValue());
    }

    public function testShouldCreateAuditForDeletedAndInsertedEntitiesFromManyToOneCollection()
    {
        $message = $this->createDummyMessage([
            'collections_updated' => [
                [
                    'entity_class' => TestAuditDataChild::class,
                    'entity_id' => 123,
                    'change_set' => [
                        'ownerManyToOne' => [
                            null,
                            [
                                'deleted' => [
                                    [
                                        'entity_class' => TestAuditDataOwner::class,
                                        'entity_id' => 321,
                                    ]
                                ],
                                'inserted' => [
                                    [
                                        'entity_class' => TestAuditDataOwner::class,
                                        'entity_id' => 567,
                                    ]
                                ],
                                'changed' => [],
                            ]
                        ]
                    ]
                ]
            ],
        ]);

        /** @var AuditChangedEntitiesRelationsProcessor $processor */
        $processor = $this->getContainer()->get('oro_dataaudit.async.audit_changed_entities_relations');

        $processor->process($message, new NullSession());

        $this->assertStoredAuditCount(1);

        $audit = $this->findLastStoredAudit();
        $this->assertCount(1, $audit->getFields());

        $auditField = $audit->getField('ownerManyToOne');
        $this->assertInstanceOf(AuditField::class, $auditField);

        $this->assertSame($audit, $auditField->getAudit());
        $this->assertSame('text', $auditField->getDataType());
        $this->assertSame('ownerManyToOne', $auditField->getField());
        $this->assertEquals(
            "Added: TestAuditDataOwner::567\nRemoved: TestAuditDataOwner::321",
            $auditField->getNewValue()
        );
        $this->assertEquals(null, $auditField->getOldValue());
    }
}
