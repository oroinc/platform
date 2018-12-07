<?php
namespace Oro\Bundle\DataAuditBundle\Tests\Functional;

use Oro\Bundle\DataAuditBundle\Async\AuditChangedEntitiesProcessor;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\DataAuditBundle\Entity\AuditField;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataChild;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataOwner;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\Status;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Transport\Null\NullSession;

/**
 * @dbIsolationPerTest
 */
class AuditInsertedEntitiesTest extends WebTestCase
{
    use AuditChangedEntitiesExtensionTrait;
    
    protected function setUp()
    {
        $this->initClient();
    }

    public function provideScalarProperties()
    {
        return [
            'stringProperty' => ['stringProperty', null, '']
        ];
    }

    public function testShouldNotCreateAuditEntityForInsertedEntityWithoutChanges()
    {
        $message = $this->createDummyMessage([
            'entities_inserted' => [
                [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id' => 123
                ]
            ],
        ]);

        /** @var AuditChangedEntitiesProcessor $processor */
        $processor = $this->getContainer()->get('oro_dataaudit.async.audit_changed_entities');

        $processor->process($message, new NullSession());

        $this->assertStoredAuditCount(0);
    }


    public function testShouldCreateAuditForInsertedEntity()
    {
        $expectedLoggedAt = new \DateTime('2012-02-01 03:02:01+0000');

        $message = $this->createDummyMessage([
            'timestamp' => $expectedLoggedAt->getTimestamp(),
            'transaction_id' => 'theTransactionId',
            'owner_description' => 'Some Owner Description',
            'entities_inserted' => [
                [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id' => 123,
                    'change_set' => [
                        'stringProperty' => [null, 'aNewValue'],
                    ],
                ]
            ],
        ]);

        /** @var AuditChangedEntitiesProcessor $processor */
        $processor = $this->getContainer()->get('oro_dataaudit.async.audit_changed_entities');

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
        $this->assertEquals('Some Owner Description', $audit->getOwnerDescription());
        $this->assertNull($audit->getUser());
        $this->assertNull($audit->getOrganization());
    }

    public function testShouldSkipAuditCreationForInsertedNotAuditableEntity()
    {
        $message = $this->createDummyMessage([
            'entities_inserted' => [
                [
                    'entity_class' => Status::class,
                    'entity_id' => 123,
                    'change_set' => [
                        'status' => [null, 'aNewValue'],
                    ],
                ]
            ],
        ]);

        /** @var AuditChangedEntitiesProcessor $processor */
        $processor = $this->getContainer()->get('oro_dataaudit.async.audit_changed_entities');

        $processor->process($message, new NullSession());

        $this->assertStoredAuditCount(0);
    }

    public function testShouldCreateAuditsForInsertedEntities()
    {
        $message = $this->createDummyMessage([
            'entities_inserted' => [
                [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id' => 123,
                    'change_set' => [
                        'stringProperty' => [null, 'aNewValue'],
                    ],
                ],
                [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id' => 321,
                    'change_set' => [
                        'stringProperty' => [null, 'aNewValue'],
                    ],
                ]
            ],
        ]);

        /** @var AuditChangedEntitiesProcessor $processor */
        $processor = $this->getContainer()->get('oro_dataaudit.async.audit_changed_entities');

        $processor->process($message, new NullSession());

        $this->assertStoredAuditCount(2);
    }

    public function testShouldCreateAuditForInsertedEntityAndSetUserIfPresent()
    {
        $user = $this->findAdmin();

        $message = $this->createDummyMessage([
            'user_id' => $user->getId(),
            'user_class' => User::class,
            'entities_inserted' => [
                [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id' => 123,
                    'change_set' => [
                        'stringProperty' => [null, 'aNewValue'],
                    ],
                ],
            ],
        ]);

        /** @var AuditChangedEntitiesProcessor $processor */
        $processor = $this->getContainer()->get('oro_dataaudit.async.audit_changed_entities');

        $processor->process($message, new NullSession());

        $this->assertStoredAuditCount(1);
        $audit = $this->findLastStoredAudit();

        $this->assertInstanceOf(User::class, $audit->getUser());
        $this->assertSame($user->getId(), $audit->getUser()->getId());
    }

    public function testShouldCreateAuditForInsertedEntityAndSetOrganizationIfPresent()
    {
        $organization = new Organization();
        $organization->setName('anOrganizationName');
        $organization->setEnabled(true);
        $this->getEntityManager()->persist($organization);
        $this->getEntityManager()->flush();

        $message = $this->createDummyMessage([
            'organization_id' => $organization->getId(),
            'entities_inserted' => [
                [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id' => 123,
                    'change_set' => [
                        'stringProperty' => [null, 'aNewValue'],
                    ],
                ],
            ],
        ]);

        /** @var AuditChangedEntitiesProcessor $processor */
        $processor = $this->getContainer()->get('oro_dataaudit.async.audit_changed_entities');

        $processor->process($message, new NullSession());

        $this->assertStoredAuditCount(1);
        $audit = $this->findLastStoredAudit();

        $this->assertInstanceOf(Organization::class, $audit->getOrganization());
        $this->assertSame($organization->getId(), $audit->getOrganization()->getId());
    }

    public function testShouldCreateAuditEntityForInsertedEntityWithStringPropertyChanged()
    {
        $message = $this->createDummyMessage([
            'transaction_id' => 'aTransactionId',
            'entities_inserted' => [
                [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id' => 123,
                    'change_set' => [
                        'stringProperty' => [null, 'theNewValue'],
                    ],
                ]
            ],
        ]);

        /** @var AuditChangedEntitiesProcessor $processor */
        $processor = $this->getContainer()->get('oro_dataaudit.async.audit_changed_entities');

        $processor->process($message, new NullSession());

        $this->assertStoredAuditCount(1);
        $audit = $this->findLastStoredAudit();

        $this->assertNotNull($audit->getId());
        $this->assertEquals(Audit::ACTION_CREATE, $audit->getAction());

        $this->assertCount(1, $audit->getFields());

        $auditField = $audit->getField('stringProperty');
        $this->assertInstanceOf(AuditField::class, $auditField);

        $this->assertSame($audit, $auditField->getAudit());
        $this->assertSame('text', $auditField->getDataType());
        $this->assertSame('stringProperty', $auditField->getField());
        $this->assertSame('theNewValue', $auditField->getNewValue());
        $this->assertSame(null, $auditField->getOldValue());
    }

    public function testShouldCreateAuditEntityForInsertedEntityWithIntPropertyChanged()
    {
        $message = $this->createDummyMessage([
            'entities_inserted' => [
                [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id' => 123,
                    'change_set' => [
                        'intProperty' => [null, 123],
                    ],
                ]
            ],
        ]);

        /** @var AuditChangedEntitiesProcessor $processor */
        $processor = $this->getContainer()->get('oro_dataaudit.async.audit_changed_entities');

        $processor->process($message, new NullSession());

        $this->assertStoredAuditCount(1);
        $audit = $this->findLastStoredAudit();

        $this->assertNotNull($audit->getId());
        $this->assertEquals(Audit::ACTION_CREATE, $audit->getAction());

        $this->assertCount(1, $audit->getFields());

        $auditField = $audit->getField('intProperty');
        $this->assertInstanceOf(AuditField::class, $auditField);

        $this->assertSame($audit, $auditField->getAudit());
        $this->assertSame('integer', $auditField->getDataType());
        $this->assertSame('intProperty', $auditField->getField());
        $this->assertSame(123, $auditField->getNewValue());
        $this->assertSame(null, $auditField->getOldValue());
    }

    public function testShouldCreateAuditEntityForInsertedEntityWithSerializedPropertyChanged()
    {
        $expectedNewVal = serialize(['foo' => 'bar']);

        $message = $this->createDummyMessage([
            'entities_inserted' => [
                [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id' => 123,
                    'change_set' => [
                        'serializedProperty' => [null, $expectedNewVal],
                    ],
                ]
            ],
        ]);

        /** @var AuditChangedEntitiesProcessor $processor */
        $processor = $this->getContainer()->get('oro_dataaudit.async.audit_changed_entities');

        $processor->process($message, new NullSession());

        $this->assertStoredAuditCount(1);
        $audit = $this->findLastStoredAudit();

        $this->assertNotNull($audit->getId());
        $this->assertEquals(Audit::ACTION_CREATE, $audit->getAction());

        $this->assertCount(1, $audit->getFields());

        $auditField = $audit->getField('serializedProperty');
        $this->assertInstanceOf(AuditField::class, $auditField);

        $this->assertSame($audit, $auditField->getAudit());
        $this->assertSame('object', $auditField->getDataType());
        $this->assertSame('serializedProperty', $auditField->getField());
        $this->assertSame($expectedNewVal, $auditField->getNewValue());
        $this->assertSame(null, $auditField->getOldValue());
    }

    public function testShouldCreateAuditEntityForInsertedEntityWithJsonPropertyChanged()
    {
        $expectedNewVal = json_encode(['foo' => 'bar']);

        $message = $this->createDummyMessage([
            'entities_inserted' => [
                [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id' => 123,
                    'change_set' => [
                        'jsonProperty' => [null, $expectedNewVal],
                    ],
                ]
            ],
        ]);

        /** @var AuditChangedEntitiesProcessor $processor */
        $processor = $this->getContainer()->get('oro_dataaudit.async.audit_changed_entities');

        $processor->process($message, new NullSession());

        $this->assertStoredAuditCount(1);
        $audit = $this->findLastStoredAudit();

        $this->assertNotNull($audit->getId());
        $this->assertEquals(Audit::ACTION_CREATE, $audit->getAction());

        $this->assertCount(1, $audit->getFields());

        $auditField = $audit->getField('jsonProperty');
        $this->assertInstanceOf(AuditField::class, $auditField);

        $this->assertSame($audit, $auditField->getAudit());
        $this->assertSame('jsonarray', $auditField->getDataType());
        $this->assertSame('jsonProperty', $auditField->getField());
        $this->assertSame($expectedNewVal, $auditField->getNewValue());
        $this->assertSame(null, $auditField->getOldValue());
    }

    public function testShouldCreateAuditEntityForInsertedEntityWithDateTimePropertyChanged()
    {
        $expectedNewVal = new \DateTime('2015-04-03 09:08:07+0000');

        $message = $this->createDummyMessage([
            'entities_inserted' => [
                [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id' => 123,
                    'change_set' => [
                        'dateProperty' => [null, $expectedNewVal->format(DATE_ISO8601)],
                    ],
                ]
            ],
        ]);

        /** @var AuditChangedEntitiesProcessor $processor */
        $processor = $this->getContainer()->get('oro_dataaudit.async.audit_changed_entities');

        $processor->process($message, new NullSession());

        $this->assertStoredAuditCount(1);
        $audit = $this->findLastStoredAudit();

        $this->assertNotNull($audit->getId());
        $this->assertEquals(Audit::ACTION_CREATE, $audit->getAction());

        $this->assertCount(1, $audit->getFields());

        $auditField = $audit->getField('dateProperty');
        $this->assertInstanceOf(AuditField::class, $auditField);

        $this->assertSame($audit, $auditField->getAudit());
        $this->assertSame('datetime', $auditField->getDataType());
        $this->assertSame('dateProperty', $auditField->getField());
        $this->assertEquals($expectedNewVal, $auditField->getNewValue());
        $this->assertSame(null, $auditField->getOldValue());
    }

    public function testShouldNotCreateAuditEntityForInsertedEntityIfOnlyNotAuditableFieldChangedButEntityAuditable()
    {
        $message = $this->createDummyMessage([
            'entities_inserted' => [
                [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id' => 123,
                    'change_set' => [
                        'notAuditableProperty' => [null, 'aNewVal'],
                    ],
                ]
            ],
        ]);

        /** @var AuditChangedEntitiesProcessor $processor */
        $processor = $this->getContainer()->get('oro_dataaudit.async.audit_changed_entities');

        $processor->process($message, new NullSession());

        $this->assertStoredAuditCount(0);
    }

    public function testShouldCreateAuditEntityForInsertedEntityIgnoringNotAuditableFields()
    {
        $message = $this->createDummyMessage([
            'transaction_id' => 'aTransactionId',
            'entities_inserted' => [
                [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id' => 123,
                    'change_set' => [
                        'stringProperty' => [null, 'theNewValue'],
                        'notAuditableProperty' => [null, 'aNewVal'],
                    ],
                ]
            ],
        ]);

        /** @var AuditChangedEntitiesProcessor $processor */
        $processor = $this->getContainer()->get('oro_dataaudit.async.audit_changed_entities');

        $processor->process($message, new NullSession());

        $this->assertStoredAuditCount(1);

        $audit = $this->findLastStoredAudit();
        $this->assertCount(1, $audit->getFields());
    }

    public function testShouldCreateAuditEntityForInsertedEntityWithOneToOneRelationChanged()
    {
        $message = $this->createDummyMessage([
            'entities_inserted' => [
                [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id' => 123,
                    'change_set' => [
                        'child' => [
                            null,
                            [
                                'entity_class' => TestAuditDataChild::class,
                                'entity_id' => 54321,
                            ]
                        ],
                    ],
                ]
            ],
        ]);

        /** @var AuditChangedEntitiesProcessor $processor */
        $processor = $this->getContainer()->get('oro_dataaudit.async.audit_changed_entities');

        $processor->process($message, new NullSession());

        $this->assertStoredAuditCount(1);
        $audit = $this->findLastStoredAudit();

        $this->assertNotNull($audit->getId());
        $this->assertEquals(Audit::ACTION_CREATE, $audit->getAction());

        $this->assertCount(1, $audit->getFields());

        $auditField = $audit->getField('child');
        $this->assertInstanceOf(AuditField::class, $auditField);

        $this->assertSame($audit, $auditField->getAudit());
        $this->assertSame('text', $auditField->getDataType());
        $this->assertSame('child', $auditField->getField());
        $this->assertEquals('TestAuditDataChild::54321', $auditField->getNewValue());
        $this->assertSame(null, $auditField->getOldValue());
    }
}
