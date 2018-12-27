<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Oro\Bundle\DataAuditBundle\Async\Topics;
use Oro\Bundle\DataAuditBundle\EventListener\SendChangedEntitiesToMessageQueueListener;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataChild;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataOwner;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationToken;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Functional\Helper\AdminUserTrait;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SendChangedEntitiesToMessageQueueListenerTest extends WebTestCase
{
    use MessageQueueExtension;
    use AdminUserTrait;

    protected function setUp()
    {
        parent::setUp();

        $this->initClient();
    }

    /**
     * @return SendChangedEntitiesToMessageQueueListener
     */
    protected function getListener()
    {
        return $this->getContainer()->get('oro_dataaudit.listener.send_changed_entities_to_message_queue');
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getContainer()->get('doctrine.orm.entity_manager');
    }

    /**
     * @return TokenStorageInterface
     */
    protected function getTokenStorage()
    {
        return $this->getContainer()->get('security.token_storage');
    }

    /**
     * @param array $expectedChanges
     */
    protected static function assertSentChanges(array $expectedChanges)
    {
        /** @var Message $message */
        $message = self::getSentMessage(Topics::ENTITIES_CHANGED);
        $body = $message->getBody();
        self::assertEquals(
            $expectedChanges,
            [
                'entities_inserted'   => $body['entities_inserted'],
                'entities_deleted'    => $body['entities_deleted'],
                'entities_updated'    => $body['entities_updated'],
                'collections_updated' => $body['collections_updated']
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
        $listener = $this->getListener();

        self::assertAttributeEquals(true, 'enabled', $listener);
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

        self::assertMessagesEmpty(Topics::ENTITIES_CHANGED);
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

        /** @var Message $message */
        $message = self::getSentMessage(Topics::ENTITIES_CHANGED);

        self::assertInstanceOf(Message::class, $message);

        $body = $message->getBody();

        self::assertArrayHasKey('timestamp', $body);
        self::assertArrayHasKey('transaction_id', $body);

        self::assertArrayHasKey('entities_updated', $body);
        self::assertInternalType('array', $body['entities_updated']);

        self::assertArrayHasKey('entities_deleted', $body);
        self::assertInternalType('array', $body['entities_deleted']);

        self::assertArrayHasKey('entities_inserted', $body);
        self::assertInternalType('array', $body['entities_inserted']);

        self::assertArrayHasKey('collections_updated', $body);
        self::assertInternalType('array', $body['collections_updated']);
    }

    public function testShouldSendMessageWithVeryLowPriority()
    {
        $em = $this->getEntityManager();

        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('aString');
        $em->persist($owner);

        $em->flush();

        /** @var Message $message */
        $message = self::getSentMessage(Topics::ENTITIES_CHANGED);
        self::assertEquals(MessagePriority::VERY_LOW, $message->getPriority());
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

        /** @var Message $message */
        $message = self::getSentMessage(Topics::ENTITIES_CHANGED);
        $body = $message->getBody();

        self::assertArrayHasKey('timestamp', $body);
        self::assertNotEmpty($body['timestamp']);

        self::assertGreaterThan(time() - 10, $body['timestamp']);
        self::assertLessThan(time() + 10, $body['timestamp']);
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

        /** @var Message $message */
        $message = self::getSentMessage(Topics::ENTITIES_CHANGED);
        $body = $message->getBody();

        self::assertArrayHasKey('transaction_id', $body);
        self::assertNotEmpty($body['transaction_id']);
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
                [
                    'entity_class' => get_class($owner),
                    'entity_id'    => $owner->getId(),
                    'change_set'   => [
                        'stringProperty' => [null, 'aString']
                    ],
                    'additional_fields' => ['field' => 'value']
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
                [
                    'entity_class' => get_class($owner),
                    'entity_id'    => $owner->getId(),
                    'change_set'   => [
                        'stringProperty' => ['aString', 'anotherString']
                    ],
                    'additional_fields' => ['field_array' => ['value' => 1]]
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
                [
                    'entity_class' => get_class($owner),
                    'entity_id'    => $removedOwnerId,
                    'additional_fields' => ['date' => '2017-11-10T10:00:00+0000']
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
        $toBeAddedChild->setAdditionalFields(['id' => 2]);
        $em->persist($toBeAddedChild);
        $owner = new TestAuditDataOwner();
        $em->persist($owner);
        $em->flush();
        self::getMessageCollector()->clear();

        $owner->getChildrenManyToMany()->add($toBeAddedChild);
        $em->flush();

        self::assertSentChanges([
            'entities_inserted'   => [],
            'entities_deleted'    => [],
            'entities_updated'    => [
                [
                    'entity_class' => get_class($owner),
                    'entity_id'    => $owner->getId()
                ]
            ],
            'collections_updated' => [
                [
                    'entity_class' => get_class($owner),
                    'entity_id'    => $owner->getId(),
                    'change_set'   => [
                        'childrenManyToMany' => [
                            null,
                            [
                                'inserted' => [
                                    [
                                        'entity_class' => get_class($toBeAddedChild),
                                        'entity_id'    => $toBeAddedChild->getId(),
                                        'additional_fields' => ['id' => 2]
                                    ]
                                ],
                                'deleted'  => [],
                                'changed'  => []
                            ]
                        ]
                    ]
                ]
            ]
        ]);
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
                [
                    'entity_class' => get_class($owner),
                    'entity_id'    => $owner->getId()
                ]
            ],
            'collections_updated' => [
                [
                    'entity_class' => get_class($owner),
                    'entity_id'    => $owner->getId(),
                    'change_set'   => [
                        'childrenManyToMany' => [
                            null,
                            [
                                'inserted' => [],
                                'deleted'  => [
                                    [
                                        'entity_class' => get_class($toBeRemovedChild),
                                        'entity_id'    => $toBeRemovedChild->getId()
                                    ]
                                ],
                                'changed'  => []
                            ]
                        ]
                    ]
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
                [
                    'entity_class' => get_class($child),
                    'entity_id'    => $child->getId(),
                    'change_set'   => [
                        'owners' => [
                            null,
                            [
                                'inserted' => [
                                    [
                                        'entity_class' => get_class($toBeAddedOwner),
                                        'entity_id'    => $toBeAddedOwner->getId()
                                    ]
                                ],
                                'deleted'  => [],
                                'changed'  => []
                            ]
                        ]
                    ]
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
                [
                    'entity_class' => get_class($child),
                    'entity_id'    => $child->getId(),
                    'change_set'   => [
                        'owners' => [
                            null,
                            [
                                'inserted' => [],
                                'deleted'  => [
                                    [
                                        'entity_class' => get_class($toBeRemovedOwner),
                                        'entity_id'    => $toBeRemovedOwner->getId()
                                    ]
                                ],
                                'changed'  => []
                            ]
                        ]
                    ]
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
                [
                    'entity_class' => get_class($owner),
                    'entity_id'    => $owner->getId(),
                    'change_set'   => [
                        'child' => [
                            null,
                            ['entity_class' => get_class($toBeSetChild), 'entity_id' => $toBeSetChild->getId()]
                        ]
                    ]
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
                [
                    'entity_class' => get_class($owner),
                    'entity_id'    => $owner->getId(),
                    'change_set'   => [
                        'child' => [
                            ['entity_class' => get_class($toBeUnsetChild), 'entity_id' => $toBeUnsetChild->getId()],
                            null
                        ]
                    ]
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

        self::assertMessagesEmpty(Topics::ENTITIES_CHANGED);
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
                [
                    'entity_class' => get_class($owner),
                    'entity_id'    => $owner->getId(),
                    'change_set'   => [
                        'ownerManyToOne' => [
                            null,
                            ['entity_class' => get_class($toBeSetChild), 'entity_id' => $toBeSetChild->getId()]
                        ]
                    ]
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
                [
                    'entity_class' => get_class($owner),
                    'entity_id'    => $owner->getId(),
                    'change_set'   => [
                        'ownerManyToOne' => [
                            ['entity_class' => get_class($toBeUnsetChild), 'entity_id' => $toBeUnsetChild->getId()],
                            null
                        ]
                    ]
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
                [
                    'entity_class' => get_class($owner),
                    'entity_id'    => $owner->getId(),
                    'change_set'   => [
                        'ownerManyToOne' => [
                            null,
                            ['entity_class' => get_class($child), 'entity_id' => $child->getId()]
                        ]
                    ]
                ]
            ],
            'collections_updated' => [
                [
                    'entity_class' => get_class($child),
                    'entity_id'    => $child->getId(),
                    'change_set'   => [
                        'childrenOneToMany' => [
                            null,
                            [
                                'inserted' => [
                                    ['entity_class' => get_class($owner), 'entity_id' => $owner->getId()]
                                ],
                                'deleted'  => [],
                                'changed'  => []
                            ]
                        ]
                    ]
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
                [
                    'entity_class' => get_class($owner),
                    'entity_id'    => $owner->getId(),
                    'change_set'   => [
                        'ownerManyToOne' => [
                            ['entity_class' => get_class($child), 'entity_id' => $child->getId()],
                            null
                        ]
                    ]
                ]
            ],
            'collections_updated' => [
                [
                    'entity_class' => get_class($child),
                    'entity_id'    => $child->getId(),
                    'change_set'   => [
                        'childrenOneToMany' => [
                            null,
                            [
                                'inserted' => [],
                                'deleted'  => [
                                    ['entity_class' => get_class($owner), 'entity_id' => $owner->getId()]
                                ],
                                'changed'  => []
                            ]
                        ]
                    ]
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
        $em->persist($toBeUpdateEntity);
        $em->flush();
        self::getMessageCollector()->clear();

        $toBeUpdateEntity->setStringProperty('anotherString');
        $em->remove($toBeDeletedEntity);

        $toBeInsertedEntity = new TestAuditDataOwner();
        $toBeInsertedEntity->setStringProperty('aString');
        $em->persist($toBeInsertedEntity);

        $em->flush();

        self::assertMessageSent(Topics::ENTITIES_CHANGED);
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

        /** @var Message $message */
        $message = self::getSentMessage(Topics::ENTITIES_CHANGED);
        $body = $message->getBody();

        self::assertArrayNotHasKey('user_id', $body);
        self::assertArrayNotHasKey('user_class', $body);
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

        /** @var Message $message */
        $message = self::getSentMessage(Topics::ENTITIES_CHANGED);
        $body = $message->getBody();

        self::assertArrayHasKey('user_id', $body);
        self::assertSame(123, $body['user_id']);

        self::assertArrayHasKey('user_class', $body);
        self::assertSame(User::class, $body['user_class']);
    }

    public function testShouldSendOwnerDescriptionIfPresent()
    {
        $organization = new Organization();
        $token = new OrganizationToken($organization);
        $token->setAttribute('owner_description', 'Test Description');

        $tokenStorage = $this->getTokenStorage();
        $tokenStorage->setToken($token);

        $em = $this->getEntityManager();

        $entity = new TestAuditDataOwner();
        $entity->setStringProperty('aString');
        $em->persist($entity);
        $em->flush();

        /** @var Message $message */
        $message = self::getSentMessage(Topics::ENTITIES_CHANGED);
        $body = $message->getBody();

        self::assertArrayHasKey('owner_description', $body);
        self::assertSame('Test Description', $body['owner_description']);
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

        /** @var Message $message */
        $message = self::getSentMessage(Topics::ENTITIES_CHANGED);
        $body = $message->getBody();

        self::assertArrayHasKey('organization_id', $body);
        self::assertSame(123, $body['organization_id']);
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

        /** @var Message $message */
        $message = self::getSentMessage(Topics::ENTITIES_CHANGED);
        $body = $message->getBody();

        self::assertArrayHasKey('impersonation_id', $body);
        self::assertSame(69, $body['impersonation_id']);
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
        $this->assertEquals(Topics::ENTITIES_CHANGED, $additionalMessage['topic']);
        $expectedEntitiesUpdated = [
            [
                'entity_class' => TestAuditDataOwner::class,
                'entity_id' => $entity->getId(),
                'change_set' => $additionalChanges
            ]
        ];
        $this->assertEquals($expectedEntitiesUpdated, $additionalMessage['message']->getBody()['entities_updated']);
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
        $this->assertEquals(Topics::ENTITIES_CHANGED, $additionalMessage['topic']);
        $expectedEntitiesUpdated = [
            [
                'entity_class' => TestAuditDataOwner::class,
                'entity_id' => $entity->getId(),
                'change_set' => array_merge(['stringProperty' => ['string', 'new string']], $additionalChanges)
            ]
        ];
        $this->assertEquals($expectedEntitiesUpdated, $additionalMessage['message']->getBody()['entities_updated']);
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
                [
                    'entity_class' => get_class($owner),
                    'entity_id'    => $updatedOwnerId,
                    'change_set'   => [
                        'stringProperty' => ['aString', 'anotherString']
                    ],
                    'additional_fields' => ['field_array' => ['value' => 1]]
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

        // remove ID from entity object to test that this entity will be skipped
        $owner->setId(null);

        $em->remove($owner);
        $em->flush();

        self::assertMessagesEmpty(Topics::ENTITIES_CHANGED);
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

        $owner->setId(null);

        $em->remove($owner);
        $em->flush();

        self::assertSentChanges([
            'entities_inserted'   => [],
            'entities_deleted'    => [
                [
                    'entity_class' => get_class($owner),
                    'entity_id'    => null,
                    'change_set' => [
                        'ownerManyToOne' => [
                            ['entity_class' => get_class($child), 'entity_id' => $child->getId()],
                            null
                        ]
                    ]
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

        /** @var Message $message */
        $message = self::getSentMessage(Topics::ENTITIES_CHANGED);
        $body = $message->getBody();

        self::assertArrayHasKey('owner_description', $body);
        self::assertSame('Integration: #1', $body['owner_description']);
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

        /** @var Message $message */
        $message = self::getSentMessage(Topics::ENTITIES_CHANGED);
        $body = $message->getBody();

        self::assertArrayHasKey('owner_description', $body);
        self::assertSame('John Doe - admin@example.com', $body['owner_description']);
    }
}
