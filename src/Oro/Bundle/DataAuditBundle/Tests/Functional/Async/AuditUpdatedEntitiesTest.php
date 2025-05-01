<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Functional\Async;

use Oro\Bundle\DataAuditBundle\Async\AuditChangedEntitiesProcessor;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\DataAuditBundle\Entity\AuditField;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataChild;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataOwner;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\Email;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AuditUpdatedEntitiesTest extends WebTestCase
{
    use AuditChangedEntitiesExtensionTrait;

    /** @var AuditChangedEntitiesProcessor */
    private $processor;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->processor = $this->getContainer()->get('oro_dataaudit.async.audit_changed_entities');
    }

    public function testShouldNotCreateAuditEntityForUpdatedEntityWithoutChanges()
    {
        $message = $this->createDummyMessage([
            'entities_updated' => [
                '000000007ec8f22c00000000536823d4' => [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id' => 123,
                    'change_set' => [],
                ]
            ],
        ]);

        $this->processor->process($message, $this->getConnection()->createSession());

        $this->assertStoredAuditCount(0);
    }

    public function testShouldCreateAuditForUpdatedEntity()
    {
        $expectedLoggedAt = new \DateTime('2012-02-01 03:02:01+0000');

        $message = $this->createDummyMessage([
            'timestamp' => $expectedLoggedAt->getTimestamp(),
            'transaction_id' => 'theTransactionId',
            'owner_description' => 'Some Owner Description',
            'entities_updated' => [
                '000000007ec8f22c00000000536823d4' => [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id' => 123,
                    'change_set' => [
                        'stringProperty' => [null, 'aNewValue'],
                    ],
                ]
            ],
        ]);

        $this->processor->process($message, $this->getConnection()->createSession());

        $this->assertStoredAuditCount(1);
        $audit = $this->findLastStoredAudit();

        $this->assertNotNull($audit->getId());
        $this->assertEquals(123, $audit->getObjectId());
        $this->assertEquals(Audit::ACTION_UPDATE, $audit->getAction());
        $this->assertEquals(TestAuditDataOwner::class, $audit->getObjectClass());
        $this->assertEquals('TestAuditDataOwner::123', $audit->getObjectName());
        $this->assertEquals(1, $audit->getVersion());
        $this->assertEquals('theTransactionId', $audit->getTransactionId());
        $this->assertEquals($expectedLoggedAt, $audit->getLoggedAt());
        $this->assertEquals('Some Owner Description', $audit->getOwnerDescription());
        $this->assertNull($audit->getUser());
        $this->assertNull($audit->getOrganization());
    }

    public function testShouldSkipAuditCreationForUpdatedNotAuditableEntity()
    {
        $message = $this->createDummyMessage([
            'entities_updated' => [
                '000000007ec8f22c00000000536823d4' => [
                    'entity_class' => Email::class,
                    'entity_id' => 123,
                    'change_set' => [
                        'status' => [null, 'aNewValue'],
                    ],
                ]
            ],
        ]);

        $this->processor->process($message, $this->getConnection()->createSession());

        $this->assertStoredAuditCount(0);
    }

    public function testShouldCreateAuditsForUpdatedEntities()
    {
        $message = $this->createDummyMessage([
            'entities_updated' => [
                '000000007ec8f22c00000000536823d4' => [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id' => 123,
                    'change_set' => [
                        'stringProperty' => [null, 'aNewValue'],
                    ],
                ],
                '000000007ec8f22f00000000536823d4' => [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id' => 321,
                    'change_set' => [
                        'stringProperty' => [null, 'aNewValue'],
                    ],
                ]
            ],
        ]);

        $this->processor->process($message, $this->getConnection()->createSession());

        $this->assertStoredAuditCount(2);
    }

    public function testShouldCreateAuditForUpdatedEntityAndSetUserIfPresent()
    {
        $user = $this->findAdmin();

        $message = $this->createDummyMessage([
            'timestamp' => time(),
            'user_id' => $user->getId(),
            'user_class' => User::class,
            'entities_updated' => [
                '000000007ec8f22c00000000536823d4' => [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id' => 123,
                    'change_set' => [
                        'stringProperty' => [null, 'aNewValue'],
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

    public function testShouldCreateAuditForUpdatedEntityAndSetOrganizationIfPresent()
    {
        $organization = new Organization();
        $organization->setName('anOrganizationName');
        $organization->setEnabled(true);
        $this->getEntityManager()->persist($organization);
        $this->getEntityManager()->flush();

        $message = $this->createDummyMessage([
            'organization_id' => $organization->getId(),
            'entities_updated' => [
                '000000007ec8f22c00000000536823d4' => [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id' => 123,
                    'change_set' => [
                        'stringProperty' => [null, 'aNewValue'],
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

    /** @dataProvider propertyChangeDataProvider */
    public function testShouldCreateAuditEntityForUpdatedEntityWithPropertyChanged(
        string $propertyName,
        string $expectedDataType,
        mixed $oldValue,
        mixed $newValue
    ): void {
        $message = $this->createDummyMessage([
            'entities_updated' => [
                '000000007ec8f22c00000000536823d4' => [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id' => 123,
                    'change_set' => [
                        $propertyName => [$oldValue, $newValue],
                    ],
                ]
            ],
        ]);

        $this->processor->process($message, $this->getConnection()->createSession());

        $this->assertStoredAuditCount(1);
        $audit = $this->findLastStoredAudit();

        $this->assertNotNull($audit->getId());
        $this->assertEquals(Audit::ACTION_UPDATE, $audit->getAction());

        $this->assertCount(1, $audit->getFields());

        $auditField = $audit->getField($propertyName);
        $this->assertInstanceOf(AuditField::class, $auditField);

        $this->assertSame($audit, $auditField->getAudit());
        $this->assertSame($expectedDataType, $auditField->getDataType());
        $this->assertSame($propertyName, $auditField->getField());
        $this->assertEquals($newValue, $auditField->getNewValue());
        $this->assertEquals($oldValue, $auditField->getOldValue());
    }
    public function propertyChangeDataProvider(): array
    {
        $oldArray = [1, 2, 'foo', ['a' => 'b', 'k' => 'l']];
        $newArray = [1, 3, 'bar', ['a' => 'c', 'x' => 'y']];
        return [
            'string property' => [
                'propertyName' => 'stringProperty',
                'dataType' => 'text',
                'oldValue' => 'theOldValue',
                'newValue' => 'theNewValue',
            ],
            'integer property' => [
                'propertyName' => 'integerProperty',
                'dataType' => 'integer',
                'oldValue' => 333,
                'newValue' => 123,
            ],
            'object property' => [
                'propertyName' => 'objectProperty',
                'dataType' => 'object',
                'oldValue' => 'theOldValue',
                'newValue' => serialize(['foo' => 'bar']),
            ],
            'json property' => [
                'propertyName' => 'jsonProperty',
                'dataType' => 'json',
                'oldValue' => $oldArray,
                'newValue' => $newArray,
            ],
            'json array property' => [
                'propertyName' => 'jsonArrayProperty',
                'dataType' => 'jsonarray',
                'oldValue' => $oldArray,
                'newValue' => $newArray,
            ],
        ];
    }

    public function testShouldCreateAuditEntityForUpdatedEntityWithDateTimePropertyChanged()
    {
        $expectedNewVal = new \DateTime('2015-04-03 09:08:07+0000');
        $expectedOldVal = new \DateTime('2016-09-08 01:02:03+0000');

        $message = $this->createDummyMessage([
            'entities_updated' => [
                '000000007ec8f22c00000000536823d4' => [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id' => 123,
                    'change_set' => [
                        'dateProperty' => [
                            $expectedOldVal->format(\DateTime::ISO8601),
                            $expectedNewVal->format(\DateTime::ISO8601)
                        ],
                    ],
                ]
            ],
        ]);

        $this->processor->process($message, $this->getConnection()->createSession());

        $this->assertStoredAuditCount(1);
        $audit = $this->findLastStoredAudit();

        $this->assertNotNull($audit->getId());
        $this->assertEquals(Audit::ACTION_UPDATE, $audit->getAction());

        $this->assertCount(1, $audit->getFields());

        $auditField = $audit->getField('dateProperty');
        $this->assertInstanceOf(AuditField::class, $auditField);

        $this->assertSame($audit, $auditField->getAudit());
        $this->assertSame('date', $auditField->getDataType());
        $this->assertSame('dateProperty', $auditField->getField());
        $this->assertEquals($expectedNewVal, $auditField->getNewValue());
        $this->assertEquals($expectedOldVal, $auditField->getOldValue());
    }

    public function testShouldNotCreateAuditEntityForUpdatedEntityIfOnlyNotAuditableFieldChangedButEntityAuditable()
    {
        $message = $this->createDummyMessage([
            'entities_updated' => [
                '000000007ec8f22c00000000536823d4' => [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id' => 123,
                    'change_set' => [
                        'notAuditableProperty' => ['anOldVal', 'aNewVal'],
                    ],
                ]
            ],
        ]);

        $this->processor->process($message, $this->getConnection()->createSession());

        $this->assertStoredAuditCount(0);
    }

    public function testShouldCreateAuditEntityForUpdatedEntityIgnoringNotAuditableFields()
    {
        $message = $this->createDummyMessage([
            'transaction_id' => 'aTransactionId',
            'entities_updated' => [
                '000000007ec8f22c00000000536823d4' => [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id' => 123,
                    'change_set' => [
                        'stringProperty' => ['anOldVal', 'theNewValue'],
                        'notAuditableProperty' => ['anOldVal', 'aNewVal'],
                    ],
                ]
            ],
        ]);

        $this->processor->process($message, $this->getConnection()->createSession());

        $this->assertStoredAuditCount(1);

        $audit = $this->findLastStoredAudit();
        $this->assertCount(1, $audit->getFields());
    }

    public function testShouldCreateAuditEntityForUpdatedEntityWithOneToOneRelationChanged()
    {
        $message = $this->createDummyMessage([
            'entities_updated' => [
                '000000007ec8f22c00000000536823d4' => [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id' => 123,
                    'change_set' => [
                        'child' => [
                            [
                                'entity_class' => TestAuditDataChild::class,
                                'entity_id' => 2345,
                            ],
                            [
                                'entity_class' => TestAuditDataChild::class,
                                'entity_id' => 4567,
                            ]
                        ],
                    ],
                ]
            ],
        ]);

        $this->processor->process($message, $this->getConnection()->createSession());

        $this->assertStoredAuditCount(1);
        $audit = $this->findLastStoredAudit();

        $this->assertNotNull($audit->getId());
        $this->assertEquals(Audit::ACTION_UPDATE, $audit->getAction());

        $this->assertCount(1, $audit->getFields());

        $auditField = $audit->getField('child');
        $this->assertInstanceOf(AuditField::class, $auditField);

        $this->assertSame($audit, $auditField->getAudit());
        $this->assertSame('text', $auditField->getDataType());
        $this->assertSame('child', $auditField->getField());
        $this->assertEquals('Added: TestAuditDataChild::4567', $auditField->getNewValue());
        $this->assertSame('Removed: TestAuditDataChild::2345', $auditField->getOldValue());
    }

    private function getConnection(): ConnectionInterface
    {
        return self::getContainer()->get('oro_message_queue.transport.connection');
    }
}
