<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Oro\Bundle\DataAuditBundle\Async\Topic\AuditChangedEntitiesTopic;
use Oro\Bundle\DataAuditBundle\EventListener\SendChangedEntitiesToMessageQueueListener;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataChild;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataOwner;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationToken;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class SendChangedEntitiesToMessageQueueListenerTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp(): void
    {
        $this->initClient();
        $this->getOptionalListenerManager()->enableListener(
            'oro_dataaudit.listener.send_changed_entities_to_message_queue'
        );
    }

    private function getListener(): SendChangedEntitiesToMessageQueueListener
    {
        return $this->getContainer()->get('oro_dataaudit.listener.send_changed_entities_to_message_queue');
    }

    private function getEntityManager(): EntityManager
    {
        return $this->getContainer()->get('doctrine.orm.entity_manager');
    }

    private function getTokenStorage(): TokenStorageInterface
    {
        return $this->getContainer()->get('security.token_storage');
    }

    private function getAdminUser(): User
    {
        return self::getContainer()
            ->get('doctrine')
            ->getManager()
            ->getRepository(User::class)
            ->findOneBy(['email' => self::AUTH_USER]);
    }

    private static function assertSentChanges(array $expectedChanges): void
    {
        $messageBody = self::getSentMessage(AuditChangedEntitiesTopic::getName());

        self::assertEquals(
            $expectedChanges,
            [
                'entities_inserted'   => $messageBody['entities_inserted'],
                'entities_deleted'    => $messageBody['entities_deleted'],
                'entities_updated'    => $messageBody['entities_updated'],
                'collections_updated' => $messageBody['collections_updated']
            ]
        );
    }

    public function testCouldBeGetAsServiceFromContainer()
    {
        $listener = $this->getListener();

        self::assertInstanceOf(SendChangedEntitiesToMessageQueueListener::class, $listener);
    }

    public function testShouldBeEnabledByDefault()
    {
        $em = $this->getEntityManager();
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('aString');
        $em->persist($owner);
        $em->flush();

        self::assertMessagesCount(AuditChangedEntitiesTopic::getName(), 1);
    }

    public function testShouldDoNothingIfListenerDisabled()
    {
        $em = $this->getEntityManager();

        $listener = $this->getListener();
        $listener->setEnabled(false);

        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('aString');
        $em->persist($owner);
        $em->flush();

        self::assertMessagesEmpty(AuditChangedEntitiesTopic::getName());
    }

    /**
     * Emulates case when the following chain of events happens:
     * onFlush -> onFlush -> postFlush -> postFlush
     */
    public function testShouldPostFlushNotThrowExceptionIfFlushIsCalledInPostFlushListener()
    {
        $listener = $this->getListener();

        $listener->postFlush(new PostFlushEventArgs($this->getEntityManager()));
    }

    public function testShouldSendEntitiesChangedMessageWithExpectedStructure()
    {
        $em = $this->getEntityManager();

        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('aString');
        $em->persist($owner);

        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('aString');
        $em->persist($owner);

        $em->flush();

        $messageBody = self::getSentMessage(AuditChangedEntitiesTopic::getName());

        $this->assertArrayHasKey('timestamp', $messageBody);
        $this->assertArrayHasKey('transaction_id', $messageBody);

        $this->assertArrayHasKey('entities_updated', $messageBody);
        $this->assertIsArray($messageBody['entities_updated']);

        $this->assertArrayHasKey('entities_deleted', $messageBody);
        $this->assertIsArray($messageBody['entities_deleted']);

        $this->assertArrayHasKey('entities_inserted', $messageBody);
        $this->assertIsArray($messageBody['entities_inserted']);

        $this->assertArrayHasKey('collections_updated', $messageBody);
        $this->assertIsArray($messageBody['collections_updated']);
    }

    public function testShouldSendMessageWithVeryLowPriority()
    {
        $em = $this->getEntityManager();

        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('aString');
        $em->persist($owner);

        $em->flush();

        self::assertMessageSentWithPriority(AuditChangedEntitiesTopic::getName(), MessagePriority::VERY_LOW);
    }

    public function testShouldSetTimestampToMessage()
    {
        $em = $this->getEntityManager();

        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('aString');
        $em->persist($owner);

        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('aString');
        $em->persist($owner);

        $em->flush();

        $messageBody = self::getSentMessage(AuditChangedEntitiesTopic::getName());

        self::assertArrayHasKey('timestamp', $messageBody);
        self::assertNotEmpty($messageBody['timestamp']);

        self::assertGreaterThan(time() - 10, $messageBody['timestamp']);
        self::assertLessThan(time() + 10, $messageBody['timestamp']);
    }

    public function testShouldSetTransactionIdToMessage()
    {
        $em = $this->getEntityManager();

        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('aString');
        $em->persist($owner);

        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('aString');
        $em->persist($owner);

        $em->flush();

        $messageBody = self::getSentMessage(AuditChangedEntitiesTopic::getName());

        self::assertArrayHasKey('transaction_id', $messageBody);
        self::assertNotEmpty($messageBody['transaction_id']);
    }

    public function testShouldSendInsertedEntity()
    {
        $em = $this->getEntityManager();

        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('aString');
        $owner->setAdditionalFields(['field' => 'value']);
        $em->persist($owner);
        $em->flush();

        self::assertSentChanges([
            'entities_inserted'   => [
                spl_object_hash($owner) => [
                    'entity_class' => get_class($owner),
                    'entity_id'    => $owner->getId(),
                    'change_set'   => [
                        'stringProperty' => [null, 'aString']
                    ],
                    'additional_fields' => ['field' => 'value'],
                ]
            ],
            'entities_deleted'    => [],
            'entities_updated'    => [],
            'collections_updated' => []
        ]);
    }

    public function testShouldSendUpdatedEntity()
    {
        $em = $this->getEntityManager();

        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('aString');
        $owner->setAdditionalFields(['field_array' => ['value' => 1]]);
        $em->persist($owner);
        $em->flush();
        self::getMessageCollector()->clear();

        $owner->setStringProperty('anotherString');
        $em->flush();

        self::assertSentChanges([
            'entities_inserted'   => [],
            'entities_deleted'    => [],
            'entities_updated'    => [
                spl_object_hash($owner) => [
                    'entity_class' => get_class($owner),
                    'entity_id'    => $owner->getId(),
                    'change_set'   => [
                        'stringProperty' => ['aString', 'anotherString']
                    ],
                    'additional_fields' => ['field_array' => ['value' => 1]],
                ]
            ],
            'collections_updated' => []
        ]);
    }

    public function testShouldSendDeletedEntity()
    {
        $em = $this->getEntityManager();

        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('aString');
        $owner->setAdditionalFields([
            'date' => new \DateTime('2017-11-10 10:00:00', new \DateTimeZone('Europe/London'))
        ]);
        $em->persist($owner);
        $em->flush();
        self::getMessageCollector()->clear();

        $removedOwnerId = $owner->getId();
        $em->remove($owner);
        $em->flush();

        self::assertSentChanges([
            'entities_inserted'   => [],
            'entities_deleted'    => [
                spl_object_hash($owner) => [
                    'entity_class' => get_class($owner),
                    'entity_id'    => $removedOwnerId,
                    'change_set'   => [
                        'id' => [$removedOwnerId, null],
                        'stringProperty' => [$owner->getStringProperty(), null],
                    ],
                    'additional_fields' => ['date' => '2017-11-10T10:00:00+0000'],
                    'entity_name' => $removedOwnerId,
                ]
            ],
            'entities_updated'    => [],
            'collections_updated' => []
        ]);
    }

    public function testShouldSendEntityAddedToManyToManyAssociation()
    {
        $em = $this->getEntityManager();

        $toBeAddedChild = new TestAuditDataChild();
        $toBeAddedChild->setAdditionalFields(['add_field' => 2]);
        $em->persist($toBeAddedChild);
        $owner = new TestAuditDataOwner();
        $em->persist($owner);
        $em->flush();
        self::getMessageCollector()->clear();

        $owner->getChildrenManyToMany()->add($toBeAddedChild);
        $em->flush();

        self::assertSentChanges(
            [
                'entities_inserted' => [],
                'entities_deleted' => [],
                'entities_updated' => [
                    spl_object_hash($owner) => [
                        'entity_class' => get_class($owner),
                        'entity_id' => $owner->getId(),
                    ],
                ],
                'collections_updated' => [
                    spl_object_hash($owner) => [
                        'entity_class' => get_class($owner),
                        'entity_id' => $owner->getId(),
                        'change_set' => [
                            'childrenManyToMany' => [
                                [
                                    'deleted' => [],
                                ],
                                [
                                    'inserted' => [
                                        spl_object_hash($toBeAddedChild) => [
                                            'entity_class' => get_class($toBeAddedChild),
                                            'entity_id' => $toBeAddedChild->getId(),
                                            'additional_fields' => ['add_field' => 2],
                                        ],
                                    ],
                                    'changed' => [],
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );
    }

    public function testShouldSendEntityRemovedFromManyToManyAssociation()
    {
        $em = $this->getEntityManager();

        $toBeRemovedChild = new TestAuditDataChild();
        $em->persist($toBeRemovedChild);
        $owner = new TestAuditDataOwner();
        $em->persist($owner);
        $owner->getChildrenManyToMany()->add($toBeRemovedChild);
        $em->flush();
        self::getMessageCollector()->clear();

        $owner->getChildrenManyToMany()->removeElement($toBeRemovedChild);
        $em->flush();

        self::assertSentChanges([
            'entities_inserted'   => [],
            'entities_deleted'    => [],
            'entities_updated'    => [
                spl_object_hash($owner) => [
                    'entity_class' => get_class($owner),
                    'entity_id'    => $owner->getId(),
                ]
            ],
            'collections_updated' => [
                spl_object_hash($owner) => [
                    'entity_class' => get_class($owner),
                    'entity_id'    => $owner->getId(),
                    'change_set'   => [
                        'childrenManyToMany' => [
                            [
                                'deleted' => [
                                    spl_object_hash($toBeRemovedChild) => [
                                        'entity_class' => get_class($toBeRemovedChild),
                                        'entity_id' => $toBeRemovedChild->getId(),
                                    ],
                                ],
                            ],
                            [
                                'inserted' => [],
                                'changed'  => []
                            ]
                        ]
                    ],
                ]
            ]
        ]);
    }

    public function testShouldSendEntityAddedToInverseSideOfManyToManyAssociation()
    {
        $em = $this->getEntityManager();

        $child = new TestAuditDataChild();
        $em->persist($child);
        $toBeAddedOwner = new TestAuditDataOwner();
        $em->persist($toBeAddedOwner);
        $em->flush();
        self::getMessageCollector()->clear();

        $child->getOwners()->add($toBeAddedOwner);
        $em->flush();

        self::assertSentChanges([
            'entities_inserted'   => [],
            'entities_deleted'    => [],
            'entities_updated'    => [],
            'collections_updated' => [
                spl_object_hash($child) => [
                    'entity_class' => get_class($child),
                    'entity_id'    => $child->getId(),
                    'change_set'   => [
                        'owners' => [
                            [
                                'deleted'  => [],
                            ],
                            [
                                'inserted' => [
                                    spl_object_hash($toBeAddedOwner) => [
                                        'entity_class' => get_class($toBeAddedOwner),
                                        'entity_id'    => $toBeAddedOwner->getId(),
                                    ]
                                ],
                                'changed'  => []
                            ]
                        ]
                    ],
                ]
            ]
        ]);
    }

    public function testShouldSendEntityRemovedFromInverseSideOfManyToManyAssociation()
    {
        $em = $this->getEntityManager();

        $child = new TestAuditDataChild();
        $em->persist($child);
        $toBeRemovedOwner = new TestAuditDataOwner();
        $em->persist($toBeRemovedOwner);
        $child->getOwners()->add($toBeRemovedOwner);
        $em->flush();
        self::getMessageCollector()->clear();

        $child->getOwners()->removeElement($toBeRemovedOwner);
        $em->flush();

        self::assertSentChanges([
            'entities_inserted'   => [],
            'entities_deleted'    => [],
            'entities_updated'    => [],
            'collections_updated' => [
                spl_object_hash($child) => [
                    'entity_class' => get_class($child),
                    'entity_id'    => $child->getId(),
                    'change_set'   => [
                        'owners' => [
                            [
                                'deleted'  => [
                                    spl_object_hash($toBeRemovedOwner) => [
                                        'entity_class' => get_class($toBeRemovedOwner),
                                        'entity_id'    => $toBeRemovedOwner->getId(),
                                    ]
                                ],
                            ],
                            [
                                'inserted' => [],
                                'changed'  => []
                            ]
                        ]
                    ],
                ]
            ]
        ]);
    }

    public function testShouldSendEntitySetToOneToOneAssociation()
    {
        $em = $this->getEntityManager();

        $toBeSetChild = new TestAuditDataChild();
        $em->persist($toBeSetChild);
        $owner = new TestAuditDataOwner();
        $em->persist($owner);
        $em->flush();
        self::getMessageCollector()->clear();

        $owner->setChild($toBeSetChild);
        $em->flush();

        self::assertSentChanges([
            'entities_inserted'   => [],
            'entities_deleted'    => [],
            'entities_updated'    => [
                spl_object_hash($owner) => [
                    'entity_class' => get_class($owner),
                    'entity_id'    => $owner->getId(),
                    'change_set'   => [
                        'child' => [
                            null,
                            [
                                'entity_class' => get_class($toBeSetChild),
                                'entity_id'    => $toBeSetChild->getId(),
                            ]
                        ]
                    ],
                ]
            ],
            'collections_updated' => []
        ]);
    }

    public function testShouldSendEntityUnsetToOneToOneAssociation()
    {
        $em = $this->getEntityManager();

        $toBeUnsetChild = new TestAuditDataChild();
        $em->persist($toBeUnsetChild);
        $owner = new TestAuditDataOwner();
        $em->persist($owner);
        $owner->setChild($toBeUnsetChild);
        $em->flush();
        self::getMessageCollector()->clear();

        $owner->setChild(null);
        $em->flush();

        self::assertSentChanges([
            'entities_inserted'   => [],
            'entities_deleted'    => [],
            'entities_updated'    => [
                spl_object_hash($owner) => [
                    'entity_class' => get_class($owner),
                    'entity_id'    => $owner->getId(),
                    'change_set'   => [
                        'child' => [
                            [
                                'entity_class' => get_class($toBeUnsetChild),
                                'entity_id'    => $toBeUnsetChild->getId(),
                            ],
                            null
                        ]
                    ],
                ]
            ],
            'collections_updated' => []
        ]);
    }

    public function testShouldNotSendEntitySetToInverseSideOfOneToOneAssociation()
    {
        $em = $this->getEntityManager();

        $toBeSetChild = new TestAuditDataChild();
        $em->persist($toBeSetChild);
        $owner = new TestAuditDataOwner();
        $em->persist($owner);
        $em->flush();
        self::getMessageCollector()->clear();

        $toBeSetChild->setOwner($owner);
        $em->flush();

        self::assertMessagesEmpty(AuditChangedEntitiesTopic::getName());
    }

    public function testShouldSendEntitySetToManyToOneAssociation()
    {
        $em = $this->getEntityManager();

        $owner = new TestAuditDataChild();
        $em->persist($owner);
        $toBeSetChild = new TestAuditDataOwner();
        $em->persist($toBeSetChild);
        $em->flush();
        self::getMessageCollector()->clear();

        $owner->setOwnerManyToOne($toBeSetChild);
        $em->flush();

        self::assertSentChanges([
            'entities_inserted'   => [],
            'entities_deleted'    => [],
            'entities_updated'    => [
                spl_object_hash($owner) => [
                    'entity_class' => get_class($owner),
                    'entity_id'    => $owner->getId(),
                    'change_set'   => [
                        'ownerManyToOne' => [
                            null,
                            [
                                'entity_class' => get_class($toBeSetChild),
                                'entity_id'    => $toBeSetChild->getId(),
                            ]
                        ]
                    ],
                ]
            ],
            'collections_updated' => []
        ]);
    }

    public function testShouldSendEntityUnsetToManyToOneAssociation()
    {
        $em = $this->getEntityManager();

        $owner = new TestAuditDataChild();
        $em->persist($owner);
        $toBeUnsetChild = new TestAuditDataOwner();
        $em->persist($toBeUnsetChild);
        $owner->setOwnerManyToOne($toBeUnsetChild);
        $em->flush();
        self::getMessageCollector()->clear();

        $owner->setOwnerManyToOne(null);
        $em->flush();

        self::assertSentChanges([
            'entities_inserted'   => [],
            'entities_deleted'    => [],
            'entities_updated'    => [
                spl_object_hash($owner) => [
                    'entity_class' => get_class($owner),
                    'entity_id'    => $owner->getId(),
                    'change_set'   => [
                        'ownerManyToOne' => [
                            [
                                'entity_class' => get_class($toBeUnsetChild),
                                'entity_id'    => $toBeUnsetChild->getId(),
                            ],
                            null
                        ]
                    ],
                ]
            ],
            'collections_updated' => []
        ]);
    }

    public function testShouldSendEntityWhenEntityAddedToInverseSideOfManyToOneAssociation()
    {
        $em = $this->getEntityManager();

        $owner = new TestAuditDataChild();
        $em->persist($owner);
        $child = new TestAuditDataOwner();
        $em->persist($child);
        $em->flush();
        self::getMessageCollector()->clear();

        $child->addChildrenOneToMany($owner);
        $em->flush();

        self::assertSentChanges([
            'entities_inserted'   => [],
            'entities_deleted'    => [],
            'entities_updated'    => [
                spl_object_hash($owner) => [
                    'entity_class' => get_class($owner),
                    'entity_id'    => $owner->getId(),
                    'change_set'   => [
                        'ownerManyToOne' => [
                            null,
                            [
                                'entity_class' => get_class($child),
                                'entity_id'    => $child->getId(),
                            ]
                        ]
                    ],
                ]
            ],
            'collections_updated' => [
                spl_object_hash($child) => [
                    'entity_class' => get_class($child),
                    'entity_id'    => $child->getId(),
                    'change_set'   => [
                        'childrenOneToMany' => [
                            [
                                'deleted'  => [],
                            ],
                            [
                                'inserted' => [
                                    spl_object_hash($owner) => [
                                        'entity_class' => get_class($owner),
                                        'entity_id'    => $owner->getId(),
                                    ]
                                ],
                                'changed'  => []
                            ]
                        ]
                    ],
                ]
            ]
        ]);
    }

    public function testShouldSendEntityWhenEntityRemovedFromInverseSideOfManyToOneAssociation()
    {
        $em = $this->getEntityManager();

        $owner = new TestAuditDataChild();
        $em->persist($owner);
        $child = new TestAuditDataOwner();
        $em->persist($child);
        $child->addChildrenOneToMany($owner);
        $em->flush();
        self::getMessageCollector()->clear();

        $child->removeChildrenOneToMany($owner);
        $em->flush();

        self::assertSentChanges([
            'entities_inserted'   => [],
            'entities_deleted'    => [],
            'entities_updated'    => [
                spl_object_hash($owner) => [
                    'entity_class' => get_class($owner),
                    'entity_id'    => $owner->getId(),
                    'change_set'   => [
                        'ownerManyToOne' => [
                            [
                                'entity_class' => get_class($child),
                                'entity_id'    => $child->getId(),
                            ],
                            null
                        ]
                    ],
                ]
            ],
            'collections_updated' => [
                spl_object_hash($child) => [
                    'entity_class' => get_class($child),
                    'entity_id'    => $child->getId(),
                    'change_set'   => [
                        'childrenOneToMany' => [
                            [
                                'deleted'  => [
                                    spl_object_hash($owner) => [
                                        'entity_class' => get_class($owner),
                                        'entity_id'    => $owner->getId(),
                                    ]
                                ],
                            ],
                            [
                                'inserted' => [],
                                'changed'  => []
                            ]
                        ]
                    ],
                ]
            ]
        ]);
    }

    public function testShouldSendOneMessagePerFlush()
    {
        $em = $this->getEntityManager();

        $toBeUpdateEntity = new TestAuditDataOwner();
        $toBeUpdateEntity->setStringProperty('aString');
        $em->persist($toBeUpdateEntity);
        $toBeDeletedEntity = new TestAuditDataOwner();
        $toBeDeletedEntity->setStringProperty('aString');
        $em->persist($toBeDeletedEntity);
        $em->flush();
        self::getMessageCollector()->clear();

        $toBeUpdateEntity->setStringProperty('anotherString');
        $em->remove($toBeDeletedEntity);

        $toBeInsertedEntity = new TestAuditDataOwner();
        $toBeInsertedEntity->setStringProperty('aString');
        $em->persist($toBeInsertedEntity);

        $em->flush();

        self::assertMessageSent(AuditChangedEntitiesTopic::getName());
    }

    public function testShouldSendInBatches()
    {
        $em = $this->getEntityManager();

        $toBeUpdated = [];
        $toBeDeleted = [];
        for ($i = 0; $i < 100; $i ++) {
            $toBeUpdated[$i] = new TestAuditDataOwner();
            $toBeUpdated[$i]->setStringProperty('aString');
            $em->persist($toBeUpdated[$i]);

            $toBeDeleted[$i] = new TestAuditDataOwner();
            $toBeDeleted[$i]->setStringProperty('aString');
            $em->persist($toBeDeleted[$i]);
        }

        self::getMessageCollector()->clear();

        for ($i = 0; $i < 100; $i ++) {
            $toBeUpdated[$i]->setStringProperty('anotherString');
            $em->remove($toBeUpdated[$i]);

            $toBeInsertedEntity = new TestAuditDataOwner();
            $toBeInsertedEntity->setStringProperty('aString');
            $em->persist($toBeInsertedEntity);
        }

        $em->flush();

        self::assertMessagesCount(AuditChangedEntitiesTopic::getName(), 2);
    }

    public function testShouldNotSendLoggedInUserInfoIfPresentButNotUserInstance()
    {
        $token = new UsernamePasswordToken($this->createMock(UserInterface::class), 'someCredentinals', 'aProviderKey');

        $tokenStorage = $this->getTokenStorage();
        $tokenStorage->setToken($token);

        $em = $this->getEntityManager();

        $entity = new TestAuditDataOwner();
        $entity->setStringProperty('aString');
        $em->persist($entity);
        $em->flush();

        $messageBody = self::getSentMessage(AuditChangedEntitiesTopic::getName());

        self::assertArrayNotHasKey('user_id', $messageBody);
        self::assertArrayNotHasKey('user_class', $messageBody);
    }

    public function testShouldSendLoggedInUserInfoIfPresent()
    {
        $user = new User();
        $user->setId(123);

        $token = new UsernamePasswordToken($user, 'someCredentinals', 'aProviderKey');

        $tokenStorage = $this->getTokenStorage();
        $tokenStorage->setToken($token);

        $em = $this->getEntityManager();

        $entity = new TestAuditDataOwner();
        $entity->setStringProperty('aString');
        $em->persist($entity);
        $em->flush();

        $messageBody = self::getSentMessage(AuditChangedEntitiesTopic::getName());

        self::assertArrayHasKey('user_id', $messageBody);
        self::assertSame(123, $messageBody['user_id']);

        self::assertArrayHasKey('user_class', $messageBody);
        self::assertSame(User::class, $messageBody['user_class']);
    }

    public function testShouldSendOwnerDescriptionIfPresent()
    {
        $organization = new Organization();
        $organization->setId(123);
        $token = new OrganizationToken($organization);
        $token->setAttribute('owner_description', 'Test Description');

        $tokenStorage = $this->getTokenStorage();
        $tokenStorage->setToken($token);

        $em = $this->getEntityManager();

        $entity = new TestAuditDataOwner();
        $entity->setStringProperty('aString');
        $em->persist($entity);
        $em->flush();

        $messageBody = self::getSentMessage(AuditChangedEntitiesTopic::getName());

        self::assertArrayHasKey('owner_description', $messageBody);
        self::assertSame('Test Description', $messageBody['owner_description']);
    }

    public function testShouldSendOrganizationInfoIfPresent()
    {
        $organization = new Organization();
        $organization->setId(123);

        $token = new OrganizationToken($organization);

        $tokenStorage = $this->getTokenStorage();
        $tokenStorage->setToken($token);

        $em = $this->getEntityManager();

        $entity = new TestAuditDataOwner();
        $entity->setStringProperty('aString');
        $em->persist($entity);
        $em->flush();

        $messageBody = self::getSentMessage(AuditChangedEntitiesTopic::getName());

        self::assertArrayHasKey('organization_id', $messageBody);
        self::assertSame(123, $messageBody['organization_id']);
    }

    public function testShouldSendImpersonationInfoIfPresent()
    {
        $organization = new Organization();
        $organization->setId(123);

        $token = new OrganizationToken($organization);
        $token->setAttribute('IMPERSONATION', 69);

        $tokenStorage = $this->getTokenStorage();
        $tokenStorage->setToken($token);

        $em = $this->getEntityManager();

        $entity = new TestAuditDataOwner();
        $entity->setStringProperty('aString');
        $em->persist($entity);
        $em->flush();

        $messageBody = self::getSentMessage(AuditChangedEntitiesTopic::getName());

        self::assertArrayHasKey('impersonation_id', $messageBody);
        self::assertSame(69, $messageBody['impersonation_id']);
    }

    public function testShouldSendAdditionalUpdates()
    {
        $em = $this->getEntityManager();
        $entity = new TestAuditDataOwner();
        $entity->setStringProperty('string');
        $em->persist($entity);
        $em->flush();

        $sentMessages = self::getSentMessages();
        $this->assertCount(1, $sentMessages);

        $additionalChanges = ['additionalChanges' => ['old', 'new']];
        $storage = self::getContainer()->get('oro_dataaudit.model.additional_entity_changes_to_audit_storage');
        $storage->addEntityUpdate($em, $entity, $additionalChanges);

        $em->flush();

        $sentMessages = self::getSentMessages();
        $this->assertCount(2, $sentMessages);
        $additionalMessage = end($sentMessages);
        $this->assertEquals(AuditChangedEntitiesTopic::getName(), $additionalMessage['topic']);
        $expectedEntitiesUpdated = [
            spl_object_hash($entity) => [
                'entity_class' => TestAuditDataOwner::class,
                'entity_id' => $entity->getId(),
                'change_set' => $additionalChanges,
            ]
        ];
        $this->assertEquals($expectedEntitiesUpdated, $additionalMessage['message']['entities_updated']);
    }

    public function testShouldSendCollectionUpdatesWithDifferentAssociation(): void
    {
        $em = $this->getEntityManager();
        $owner = new TestAuditDataOwner();
        $em->persist($owner);

        $manyToManyChild = new TestAuditDataChild();
        $manyToManyChildUnidirectional = new TestAuditDataChild();
        $em->persist($manyToManyChild);
        $em->persist($manyToManyChildUnidirectional);
        $em->flush();

        $owner->getChildrenManyToMany()->add($manyToManyChild);
        $owner->getChildrenManyToManyUnidirectional()->add($manyToManyChildUnidirectional);

        self::getMessageCollector()->clear();
        $em->flush();

        self::assertSentChanges(
            [
                'entities_inserted' => [],
                'entities_deleted' => [],
                'entities_updated' => [
                    spl_object_hash($owner) => [
                        'entity_class' => get_class($owner),
                        'entity_id' => $owner->getId(),
                    ],
                ],
                'collections_updated' => [
                    spl_object_hash($owner) => [
                        'entity_class' => get_class($owner),
                        'entity_id' => $owner->getId(),
                        'change_set' => [
                            'childrenManyToMany' => [
                                [
                                    'deleted' => [],
                                ],
                                [
                                    'inserted' => [
                                        spl_object_hash($manyToManyChild) => [
                                            'entity_class' => get_class($manyToManyChild),
                                            'entity_id' => $manyToManyChild->getId(),
                                        ],
                                    ],
                                    'changed' => [],
                                ],
                            ],
                            'childrenManyToManyUnidirectional' => [
                                [
                                    'deleted' => [],
                                ],
                                [
                                    'inserted' => [
                                        spl_object_hash($manyToManyChildUnidirectional) => [
                                            'entity_class' => get_class($manyToManyChildUnidirectional),
                                            'entity_id' => $manyToManyChildUnidirectional->getId(),
                                        ],
                                    ],
                                    'changed' => [],
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );
    }

    public function testShouldSendEntityChangesWithAdditionalUpdates()
    {
        $em = $this->getEntityManager();
        $entity = new TestAuditDataOwner();
        $entity->setStringProperty('string');
        $em->persist($entity);
        $em->flush();

        $sentMessages = self::getSentMessages();
        $this->assertCount(1, $sentMessages);

        $additionalChanges = ['additionalChanges' => ['old', 'new']];
        $storage = self::getContainer()->get('oro_dataaudit.model.additional_entity_changes_to_audit_storage');
        $storage->addEntityUpdate($em, $entity, $additionalChanges);

        $entity->setStringProperty('new string');
        $em->persist($entity);
        $em->flush();

        $sentMessages = self::getSentMessages();
        $this->assertCount(2, $sentMessages);
        $additionalMessage = end($sentMessages);
        $this->assertEquals(AuditChangedEntitiesTopic::getName(), $additionalMessage['topic']);
        $expectedEntitiesUpdated = [
            spl_object_hash($entity) => [
                'entity_class' => TestAuditDataOwner::class,
                'entity_id' => $entity->getId(),
                'change_set' => array_merge(['stringProperty' => ['string', 'new string']], $additionalChanges),
            ]
        ];
        $this->assertEquals($expectedEntitiesUpdated, $additionalMessage['message']['entities_updated']);
    }

    public function testShouldSendUpdatedEntityWithIdFromUnitOfWorkInsteadOfIdFromEntityObject()
    {
        $em = $this->getEntityManager();

        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('aString');
        $owner->setAdditionalFields(['field_array' => ['value' => 1]]);
        $em->persist($owner);
        $em->flush();
        self::getMessageCollector()->clear();

        $updatedOwnerId = $owner->getId();
        $owner->setStringProperty('anotherString');

        // remove ID from entity object to test that ID will be got from UnitOfWork
        $owner->setId(null);

        $em->flush();

        self::assertSentChanges([
            'entities_inserted'   => [],
            'entities_deleted'    => [],
            'entities_updated'    => [
                spl_object_hash($owner) => [
                    'entity_class' => get_class($owner),
                    'entity_id'    => $updatedOwnerId,
                    'change_set'   => [
                        'stringProperty' => ['aString', 'anotherString']
                    ],
                    'additional_fields' => ['field_array' => ['value' => 1]],
                ]
            ],
            'collections_updated' => []
        ]);
    }

    public function testShouldNotSendDeletedEntityWithEmptyId()
    {
        $em = $this->getEntityManager();

        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('aString');
        $em->persist($owner);
        $em->flush();
        self::getMessageCollector()->clear();

        $ownerId = $owner->getId();

        // remove ID from entity object to test that this entity will be skipped
        $owner->setId(null);

        $em->remove($owner);
        $em->flush();

        self::assertSentChanges([
            'entities_inserted'   => [],
            'entities_deleted'    => [
                spl_object_hash($owner) => [
                    'entity_class' => get_class($owner),
                    'entity_id'    => $ownerId,
                    'change_set' => [
                        'id' => [$ownerId, null],
                        'stringProperty' => [$owner->getStringProperty(), null],
                    ],
                ]
            ],
            'entities_updated'    => [],
            'collections_updated' => []
        ]);
    }

    public function testShouldSendDeletedEntityWithEmptyIdIfItHadNotEmptyManyToOneChildren()
    {
        $em = $this->getEntityManager();

        $owner = new TestAuditDataChild();
        $owner->setStringProperty('aString');
        $child = new TestAuditDataOwner();
        $child->setStringProperty('aChild');
        $owner->setOwnerManyToOne($child);
        $em->persist($child);
        $em->persist($owner);
        $em->flush();
        self::getMessageCollector()->clear();

        $ownerId = $owner->getId();

        $owner->setId(null);

        $em->remove($owner);
        $em->flush();

        self::assertSentChanges([
            'entities_inserted'   => [],
            'entities_deleted'    => [
                spl_object_hash($owner) => [
                    'entity_class' => get_class($owner),
                    'entity_id'    => $ownerId,
                    'change_set' => [
                        'ownerManyToOne' => [
                            [
                                'entity_class' => get_class($child),
                                'entity_id' => $child->getId(),
                            ],
                            null
                        ],
                        'id' => [$ownerId, null],
                        'stringProperty' => [$owner->getStringProperty(), null],
                    ],
                    'entity_name' => 'Item #',
                ]
            ],
            'entities_updated'    => [],
            'collections_updated' => []
        ]);
    }

    public function testShouldSendOwnerDescriptionOverridesAuthorName()
    {
        $user = $this->getAdminUser();
        $organization = $user->getOrganization();
        $token = new UsernamePasswordOrganizationToken($user, $user->getUsername(), 'main', $organization);
        $token->setAttribute('owner_description', 'Integration: #1');

        $tokenStorage = $this->getTokenStorage();
        $tokenStorage->setToken($token);

        $em = $this->getEntityManager();

        $entity = new TestAuditDataOwner();
        $entity->setStringProperty('aString');
        $em->persist($entity);
        $em->flush();

        $messageBody = self::getSentMessage(AuditChangedEntitiesTopic::getName());

        self::assertArrayHasKey('owner_description', $messageBody);
        self::assertSame('Integration: #1', $messageBody['owner_description']);
    }

    public function testShouldSendOwnerDescriptionUsingAuthorName()
    {
        $user = $this->getAdminUser();
        $token = new UsernamePasswordToken($user, $user->getUsername(), 'main');

        $tokenStorage = $this->getTokenStorage();
        $tokenStorage->setToken($token);

        $em = $this->getEntityManager();

        $entity = new TestAuditDataOwner();
        $entity->setStringProperty('aString');
        $em->persist($entity);
        $em->flush();

        $messageBody = self::getSentMessage(AuditChangedEntitiesTopic::getName());

        self::assertArrayHasKey('owner_description', $messageBody);
        self::assertSame('John Doe - admin@example.com', $messageBody['owner_description']);
    }
}
