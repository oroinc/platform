<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Async;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\IntegrationBundle\Async\Topic\ProcessSingleWebhookNotificationTopic;
use Oro\Bundle\IntegrationBundle\Async\Topic\SendWebhookNotificationTopic;
use Oro\Bundle\IntegrationBundle\Async\WebhookNotificationProcessor;
use Oro\Bundle\IntegrationBundle\Entity\Repository\WebhookProducerSettingsRepository;
use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectReference;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationTokenFactoryInterface;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Test\JobRunner;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class WebhookNotificationProcessorTest extends TestCase
{
    use EntityTrait;

    private ManagerRegistry&MockObject $registry;
    private MessageProducerInterface&MockObject $messageProducer;
    private JobRunner&MockObject $jobRunner;
    private TokenStorageInterface&MockObject $tokenStorage;
    private AuthorizationCheckerInterface&MockObject $authorizationChecker;
    private UsernamePasswordOrganizationTokenFactoryInterface&MockObject $tokenFactory;

    private WebhookNotificationProcessor $processor;

    #[\Override]
    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenFactory = $this->createMock(UsernamePasswordOrganizationTokenFactoryInterface::class);

        $this->processor = new WebhookNotificationProcessor(
            $this->registry,
            $this->messageProducer,
            $this->jobRunner,
            $this->tokenStorage,
            $this->authorizationChecker,
            $this->tokenFactory,
        );
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals(
            [SendWebhookNotificationTopic::getName()],
            WebhookNotificationProcessor::getSubscribedTopics()
        );
    }

    public function testProcessSuccessfullyWithoutEntity(): void
    {
        $message = $this->createMessage('msg_123', [
            'topic' => 'order.created',
            'event_data' => ['id' => 1],
            'timestamp' => 1234567890,
            'entity_class' => null,
            'entity_id' => null,
            'message_id' => 'test-message-id-123',
        ]);

        $webhook1 = $this->createWebhook(1);
        $webhook2 = $this->createWebhook(2);

        $repository = $this->createMock(WebhookProducerSettingsRepository::class);
        $repository->expects(self::once())
            ->method('getActiveWebhooks')
            ->with('order.created')
            ->willReturn([$webhook1, $webhook2]);

        $this->registry->expects(self::once())
            ->method('getRepository')
            ->with(WebhookProducerSettings::class)
            ->willReturn($repository);

        $this->tokenFactory->expects(self::never())
            ->method('create');

        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        $childJob = $this->setupJobRunnerMock($message);
        $this->jobRunner->expects(self::exactly(2))
            ->method('createDelayed')
            ->willReturnCallback(function ($name, $closure) use ($childJob) {
                return $closure($this->jobRunner, $childJob);
            });

        $this->messageProducer->expects(self::exactly(2))
            ->method('send')
            ->with(
                ProcessSingleWebhookNotificationTopic::getName(),
                self::callback(static function ($body) {
                    return isset($body['webhook_id'])
                        && isset($body['event_data'])
                        && isset($body['timestamp'])
                        && isset($body['job_id'])
                        && isset($body['message_id'])
                        && is_string($body['message_id'])
                        && !empty($body['message_id']);
                })
            );

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessSuccessfullyWithEntity(): void
    {
        $entity = new \stdClass();
        $entity->id = 42;
        $message = $this->createMessage('msg_123', [
            'topic' => 'order.created',
            'event_data' => ['id' => 1],
            'timestamp' => 1234567890,
            'entity_class' => 'App\Entity\Product',
            'entity_id' => 33,
            'message_id' => 'test-message-id-with-entity',
        ]);

        $webhook = $this->createWebhook(1);

        $repository = $this->createMock(WebhookProducerSettingsRepository::class);
        $repository->expects(self::once())
            ->method('getActiveWebhooks')
            ->with('order.created')
            ->willReturn([$webhook]);

        $entityRepository = $this->createMock(ObjectRepository::class);
        $entityRepository->expects(self::once())
            ->method('find')
            ->with(33)
            ->willReturn($entity);

        $this->registry->expects(self::exactly(2))
            ->method('getRepository')
            ->willReturnCallback(function ($class) use ($entityRepository, $repository) {
                return match ($class) {
                    'App\Entity\Product' => $entityRepository,
                    default => $repository,
                };
            });

        $originalToken = $this->createMock(TokenInterface::class);
        $this->tokenStorage->expects(self::exactly(2))
            ->method('getToken')
            ->willReturn($originalToken);

        $webhookToken = $this->expectTokenCreatedForWebhook($webhook);

        $setTokenCalls = [];
        $this->tokenStorage->expects(self::exactly(3))
            ->method('setToken')
            ->willReturnCallback(function ($token) use (&$setTokenCalls) {
                $setTokenCalls[] = $token;
            });

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', self::identicalTo($entity))
            ->willReturn(true);

        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(
                ProcessSingleWebhookNotificationTopic::getName(),
                self::callback(function ($body) {
                    return $body['webhook_id'] === '1'
                        && $body['event_data'] === ['id' => 1]
                        && $body['timestamp'] === 1234567890
                        && $body['job_id'] === 42
                        && $body['message_id'] === 'test-message-id-with-entity'
                        && $body['metadata'] === [
                            'entity_class' => 'App\Entity\Product',
                            'entity_id' => 33,
                        ];
                })
            );
        $childJob = $this->setupJobRunnerMock($message);
        $this->jobRunner->expects(self::once())
            ->method('createDelayed')
            ->willReturnCallback(function ($name, $closure) use ($childJob) {
                return $closure($this->jobRunner, $childJob);
            });

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
        self::assertSame([$webhookToken, $originalToken, $originalToken], $setTokenCalls);
    }

    public function testProcessSuccessfullyWithRemovedEntity(): void
    {
        $message = $this->createMessage('msg_removed', [
            'topic' => 'order.deleted',
            'event_data' => ['id' => 99],
            'timestamp' => 1234567800,
            'entity_class' => 'App\Entity\Order',
            'entity_id' => 99,
            'entity_owner_id' => 7,
            'entity_organization_id' => 3,
            'message_id' => 'test-message-id-removed',
        ]);

        $webhook = $this->createWebhook(1);

        $repository = $this->createMock(WebhookProducerSettingsRepository::class);
        $repository->expects(self::once())
            ->method('getActiveWebhooks')
            ->with('order.deleted')
            ->willReturn([$webhook]);

        $entityRepository = $this->createMock(ObjectRepository::class);
        $entityRepository->expects(self::once())
            ->method('find')
            ->with(99)
            ->willReturn(null);

        $this->registry->expects(self::exactly(2))
            ->method('getRepository')
            ->willReturnCallback(function ($class) use ($entityRepository, $repository) {
                return match ($class) {
                    'App\Entity\Order' => $entityRepository,
                    default => $repository,
                };
            });

        $originalToken = $this->createMock(TokenInterface::class);
        $this->tokenStorage->expects(self::exactly(2))
            ->method('getToken')
            ->willReturn($originalToken);

        $webhookToken = $this->expectTokenCreatedForWebhook($webhook);

        $setTokenCalls = [];
        $this->tokenStorage->expects(self::exactly(3))
            ->method('setToken')
            ->willReturnCallback(function ($token) use (&$setTokenCalls) {
                $setTokenCalls[] = $token;
            });

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with(
                'VIEW',
                self::callback(static function ($aclEntity) {
                    return $aclEntity instanceof DomainObjectReference
                        && $aclEntity->getType() === 'App\Entity\Order'
                        && $aclEntity->getIdentifier() === 99
                        && $aclEntity->getOwnerId() === 7
                        && $aclEntity->getOrganizationId() === 3;
                })
            )
            ->willReturn(true);

        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(
                ProcessSingleWebhookNotificationTopic::getName(),
                self::callback(function ($body) {
                    return $body['webhook_id'] === '1'
                        && $body['event_data'] === ['id' => 99]
                        && $body['job_id'] === 42
                        && $body['message_id'] === 'test-message-id-removed'
                        && $body['metadata'] === [
                            'entity_class' => 'App\Entity\Order',
                            'entity_id' => 99,
                        ];
                })
            );

        $childJob = $this->setupJobRunnerMock($message);
        $this->jobRunner->expects(self::once())
            ->method('createDelayed')
            ->willReturnCallback(function ($name, $closure) use ($childJob) {
                return $closure($this->jobRunner, $childJob);
            });

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
        self::assertSame([$webhookToken, $originalToken, $originalToken], $setTokenCalls);
    }

    public function testProcessSuccessfullyWithRemovedEntityWhenBodyHasNoOwnershipData(): void
    {
        $message = $this->createMessage('msg_removed_no_ownership', [
            'topic' => 'order.deleted',
            'event_data' => ['id' => 99],
            'timestamp' => 1234567800,
            'entity_class' => 'App\Entity\Order',
            'entity_id' => 99,
            'entity_owner_id' => null,
            'entity_organization_id' => null,
            'message_id' => 'test-message-id-removed-no-ownership',
        ]);

        $webhook = $this->createWebhook(1);

        $repository = $this->createMock(WebhookProducerSettingsRepository::class);
        $repository->expects(self::once())
            ->method('getActiveWebhooks')
            ->with('order.deleted')
            ->willReturn([$webhook]);

        $entityRepository = $this->createMock(ObjectRepository::class);
        $entityRepository->expects(self::once())
            ->method('find')
            ->with(99)
            ->willReturn(null);

        $this->registry->expects(self::exactly(2))
            ->method('getRepository')
            ->willReturnCallback(function ($class) use ($entityRepository, $repository) {
                return match ($class) {
                    'App\Entity\Order' => $entityRepository,
                    default => $repository,
                };
            });

        $originalToken = $this->createMock(TokenInterface::class);
        $this->tokenStorage->expects(self::atLeastOnce())
            ->method('getToken')
            ->willReturn($originalToken);
        $this->tokenStorage->expects(self::atLeastOnce())
            ->method('setToken');

        $this->expectTokenCreatedForWebhook($webhook);

        // A removed entity is always checked through a reference, no matter whether its class is owned:
        // with no ownership data in the body the reference carries neither an owner nor an organization,
        // so it stays accessible only on the global and system access levels
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with(
                'VIEW',
                self::callback(static function ($aclEntity) {
                    return $aclEntity instanceof DomainObjectReference
                        && $aclEntity->getType() === 'App\Entity\Order'
                        && $aclEntity->getIdentifier() === 99
                        && $aclEntity->getOwnerId() === 0
                        && $aclEntity->getOrganizationId() === null;
                })
            )
            ->willReturn(true);

        $childJob = $this->setupJobRunnerMock($message);
        $this->jobRunner->expects(self::once())
            ->method('createDelayed')
            ->willReturnCallback(function ($name, $closure) use ($childJob) {
                return $closure($this->jobRunner, $childJob);
            });

        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(
                ProcessSingleWebhookNotificationTopic::getName(),
                self::callback(function ($body) {
                    return $body['webhook_id'] === '1'
                        && $body['metadata'] === [
                            'entity_class' => 'App\Entity\Order',
                            'entity_id' => 99,
                        ];
                })
            );

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessSuccessfullyWithRemovedOwnedEntityWithoutOwnerButWithOrganization(): void
    {
        $message = $this->createMessage('msg_removed_no_owner', [
            'topic' => 'order.deleted',
            'event_data' => ['id' => 99],
            'timestamp' => 1234567800,
            'entity_class' => 'App\Entity\Order',
            'entity_id' => 99,
            'entity_owner_id' => null,
            'entity_organization_id' => 3,
            'message_id' => 'test-message-id-removed-no-owner',
        ]);

        $webhook = $this->createWebhook(1);

        $repository = $this->createMock(WebhookProducerSettingsRepository::class);
        $repository->expects(self::once())
            ->method('getActiveWebhooks')
            ->with('order.deleted')
            ->willReturn([$webhook]);

        $entityRepository = $this->createMock(ObjectRepository::class);
        $entityRepository->expects(self::once())
            ->method('find')
            ->with(99)
            ->willReturn(null);

        $this->registry->expects(self::exactly(2))
            ->method('getRepository')
            ->willReturnCallback(function ($class) use ($entityRepository, $repository) {
                return match ($class) {
                    'App\Entity\Order' => $entityRepository,
                    default => $repository,
                };
            });

        $this->tokenStorage->expects(self::atLeastOnce())
            ->method('getToken')
            ->willReturn($this->createMock(TokenInterface::class));
        $this->tokenStorage->expects(self::atLeastOnce())
            ->method('setToken');

        $this->expectTokenCreatedForWebhook($webhook);

        // The owner value is null, but the reference still carries the organization, so it must be enforced
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with(
                'VIEW',
                self::callback(static function ($aclEntity) {
                    return $aclEntity instanceof DomainObjectReference
                        && $aclEntity->getType() === 'App\Entity\Order'
                        && $aclEntity->getIdentifier() === 99
                        && $aclEntity->getOwnerId() === 0
                        && $aclEntity->getOrganizationId() === 3;
                })
            )
            ->willReturn(true);

        $childJob = $this->setupJobRunnerMock($message);
        $this->jobRunner->expects(self::once())
            ->method('createDelayed')
            ->willReturnCallback(function ($name, $closure) use ($childJob) {
                return $closure($this->jobRunner, $childJob);
            });

        $this->messageProducer->expects(self::once())
            ->method('send');

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessFiltersWebhooksWithoutViewPermissionForRemovedEntity(): void
    {
        $message = $this->createMessage('msg_removed_acl', [
            'topic' => 'order.deleted',
            'event_data' => ['id' => 99],
            'timestamp' => 1234567800,
            'entity_class' => 'App\Entity\Order',
            'entity_id' => 99,
            'entity_owner_id' => 7,
            'entity_organization_id' => 3,
            'message_id' => 'test-message-id-removed-acl',
        ]);

        $webhook = $this->createWebhook(1);

        $repository = $this->createMock(WebhookProducerSettingsRepository::class);
        $repository->expects(self::once())
            ->method('getActiveWebhooks')
            ->with('order.deleted')
            ->willReturn([$webhook]);

        $entityRepository = $this->createMock(ObjectRepository::class);
        $entityRepository->expects(self::once())
            ->method('find')
            ->with(99)
            ->willReturn(null);

        $this->registry->expects(self::exactly(2))
            ->method('getRepository')
            ->willReturnCallback(function ($class) use ($entityRepository, $repository) {
                return match ($class) {
                    'App\Entity\Order' => $entityRepository,
                    default => $repository,
                };
            });

        $originalToken = $this->createMock(TokenInterface::class);
        $this->tokenStorage->expects(self::atLeastOnce())
            ->method('getToken')
            ->willReturn($originalToken);
        $this->tokenStorage->expects(self::atLeastOnce())
            ->method('setToken');

        $this->expectTokenCreatedForWebhook($webhook);

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with(
                'VIEW',
                self::callback(static function ($aclEntity) {
                    return $aclEntity instanceof DomainObjectReference
                        && $aclEntity->getOwnerId() === 7
                        && $aclEntity->getOrganizationId() === 3;
                })
            )
            ->willReturn(false);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::exactly(2))
            ->method('info')
            ->withConsecutive(
                [
                    'Webhook ID {webhook_id} was skipped because of insufficient permissions',
                    [
                        'webhook_id' => '1',
                        'topic' => 'order.deleted',
                        'message_id' => 'test-message-id-removed-acl',
                    ]
                ],
                [
                    'No applicable active webhooks found for the given topic',
                    [
                        'topic' => 'order.deleted',
                        'event_data' => ['id' => 99],
                        'message_id' => 'test-message-id-removed-acl',
                    ]
                ]
            );
        $this->processor->setLogger($logger);

        $this->setupJobRunnerMock($message);
        $this->messageProducer->expects(self::never())
            ->method('send');

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    /**
     * @dataProvider invalidWebhookOwnershipDataProvider
     */
    public function testProcessSkipsWebhookWithInvalidOwnershipData(bool $hasOwner, bool $hasOrganization): void
    {
        // The webhook ownership columns are "ON DELETE SET NULL", so the row can be orphaned at any time
        $message = $this->createMessage('msg_orphan', [
            'topic' => 'product.updated',
            'event_data' => ['id' => 10],
            'timestamp' => 1234567600,
            'entity_class' => 'App\Entity\Secure',
            'entity_id' => 10,
            'message_id' => 'test-integrity-id-orphan',
        ]);

        $entity = new \stdClass();
        $entity->id = 10;

        $orphanedWebhook = $this->createWebhookWithOwnership(1, $hasOwner, $hasOrganization);
        $healthyWebhook = $this->createWebhook(2);

        $repository = $this->createMock(WebhookProducerSettingsRepository::class);
        $repository->expects(self::once())
            ->method('getActiveWebhooks')
            ->with('product.updated')
            ->willReturn([$orphanedWebhook, $healthyWebhook]);

        $entityRepository = $this->createMock(ObjectRepository::class);
        $entityRepository->expects(self::once())
            ->method('find')
            ->with(10)
            ->willReturn($entity);

        $this->registry->expects(self::exactly(2))
            ->method('getRepository')
            ->willReturnCallback(function ($class) use ($entityRepository, $repository) {
                return match ($class) {
                    'App\Entity\Secure' => $entityRepository,
                    default => $repository,
                };
            });

        $this->tokenStorage->expects(self::atLeastOnce())
            ->method('getToken')
            ->willReturn($this->createMock(TokenInterface::class));
        $this->tokenStorage->expects(self::atLeastOnce())
            ->method('setToken');

        // No token can be built for the orphaned webhook, so only the healthy one gets one
        $this->expectTokenCreatedForWebhook($healthyWebhook);

        // The orphaned webhook never reaches the ACL check, the healthy one is still evaluated
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', self::identicalTo($entity))
            ->willReturn(true);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('warning')
            ->with(
                'Webhook ID {webhook_id} has invalid ownership data',
                ['webhook_id' => '1']
            );
        $logger->expects(self::once())
            ->method('info')
            ->with(
                'Webhook ID {webhook_id} was skipped because of insufficient permissions',
                [
                    'webhook_id' => '1',
                    'topic' => 'product.updated',
                    'message_id' => 'test-integrity-id-orphan',
                ]
            );
        $this->processor->setLogger($logger);

        $childJob = $this->setupJobRunnerMock($message);
        $this->jobRunner->expects(self::once())
            ->method('createDelayed')
            ->willReturnCallback(function ($name, $closure) use ($childJob) {
                return $closure($this->jobRunner, $childJob);
            });

        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(
                ProcessSingleWebhookNotificationTopic::getName(),
                self::callback(function ($body) {
                    return $body['webhook_id'] === '2';
                })
            );

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function invalidWebhookOwnershipDataProvider(): array
    {
        return [
            'the owning user is removed' => [
                'hasOwner' => false,
                'hasOrganization' => true,
            ],
            'the owning organization is removed' => [
                'hasOwner' => true,
                'hasOrganization' => false,
            ],
        ];
    }

    public function testProcessDeliversToWebhookWithInvalidOwnershipDataWhenThereIsNoAclEntity(): void
    {
        // A payload-only notification needs no token, so the ownership guard must not reject it
        $message = $this->createMessage('msg_orphan_no_entity', [
            'topic' => 'product.updated',
            'event_data' => ['id' => 10],
            'timestamp' => 1234567600,
            'entity_class' => null,
            'entity_id' => null,
            'message_id' => 'test-integrity-id-orphan-no-entity',
        ]);

        $webhook = $this->createWebhookWithOwnership(1, false, false);

        $repository = $this->createMock(WebhookProducerSettingsRepository::class);
        $repository->expects(self::once())
            ->method('getActiveWebhooks')
            ->with('product.updated')
            ->willReturn([$webhook]);

        $this->registry->expects(self::once())
            ->method('getRepository')
            ->with(WebhookProducerSettings::class)
            ->willReturn($repository);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())
            ->method('warning');
        $logger->expects(self::never())
            ->method('info');
        $this->processor->setLogger($logger);

        $this->tokenFactory->expects(self::never())
            ->method('create');

        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        $childJob = $this->setupJobRunnerMock($message);
        $this->jobRunner->expects(self::once())
            ->method('createDelayed')
            ->willReturnCallback(function ($name, $closure) use ($childJob) {
                return $closure($this->jobRunner, $childJob);
            });

        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(
                ProcessSingleWebhookNotificationTopic::getName(),
                self::callback(function ($body) {
                    return $body['webhook_id'] === '1'
                        && $body['metadata'] === [];
                })
            );

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessSkipsEntityLookupWhenEntityClassIsNull(): void
    {
        // Entity lookup is skipped when entity_class is null
        $message = $this->createMessage('msg_no_class', [
            'topic' => 'order.deleted',
            'event_data' => ['id' => 55],
            'timestamp' => 1234567800,
            'entity_class' => null,
            'entity_id' => 55,
            'message_id' => 'test-integrity-id-no-class',
        ]);

        $repository = $this->createMock(WebhookProducerSettingsRepository::class);
        $repository->expects(self::once())
            ->method('getActiveWebhooks')
            ->with('order.deleted')
            ->willReturn([]);

        $this->registry->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);

        $this->setupJobRunnerMock($message);

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessRejectWhenEventDataIsEmpty(): void
    {
        // A webhook notification without event data is an impossible message, so it is rejected
        $message = $this->createMessage('msg_empty_data', [
            'topic' => 'order.updated',
            'event_data' => [],
            'timestamp' => 1234567800,
            'entity_class' => 'App\Entity\Order',
            'entity_id' => 55,
            'message_id' => 'test-integrity-id-empty-data',
        ]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('warning')
            ->with(
                'Webhook notification has no event data',
                [
                    'topic' => 'order.updated',
                    'entity_class' => 'App\Entity\Order',
                    'entity_id' => 55,
                    'message_id' => 'test-integrity-id-empty-data'
                ]
            );
        $this->processor->setLogger($logger);

        $this->registry->expects(self::never())
            ->method('getRepository');

        $this->jobRunner->expects(self::never())
            ->method('runUniqueByMessage');

        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        $this->messageProducer->expects(self::never())
            ->method('send');

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testProcessRejectWhenEventDataIsEmptyAndThereIsNoEntity(): void
    {
        // The empty event data guard applies to payload-only notifications as well
        $message = $this->createMessage('msg_empty_data_no_entity', [
            'topic' => 'order.updated',
            'event_data' => [],
            'timestamp' => 1234567800,
            'entity_class' => null,
            'entity_id' => null,
            'message_id' => 'test-integrity-id-empty-data-no-entity',
        ]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('warning')
            ->with(
                'Webhook notification has no event data',
                [
                    'topic' => 'order.updated',
                    'entity_class' => null,
                    'entity_id' => null,
                    'message_id' => 'test-integrity-id-empty-data-no-entity'
                ]
            );
        $this->processor->setLogger($logger);

        $this->registry->expects(self::never())
            ->method('getRepository');

        $this->jobRunner->expects(self::never())
            ->method('runUniqueByMessage');

        $this->messageProducer->expects(self::never())
            ->method('send');

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testProcessAckWhenNoActiveWebhooks(): void
    {
        $message = $this->createMessage('msg_empty', [
            'topic' => 'order.empty',
            'event_data' => ['id' => 77],
            'timestamp' => 1234567700,
            'entity_class' => null,
            'entity_id' => null,
            'message_id' => 'test-integrity-id-no-webhooks',
        ]);

        $repository = $this->createMock(WebhookProducerSettingsRepository::class);
        $repository->expects(self::once())
            ->method('getActiveWebhooks')
            ->with('order.empty')
            ->willReturn([]);

        $this->registry->expects(self::once())
            ->method('getRepository')
            ->with(WebhookProducerSettings::class)
            ->willReturn($repository);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('info')
            ->with(
                'No applicable active webhooks found for the given topic',
                [
                    'topic' => 'order.empty',
                    'event_data' => ['id' => 77],
                    'message_id' => 'test-integrity-id-no-webhooks'
                ]
            );

        $this->processor->setLogger($logger);

        $this->setupJobRunnerMock($message);
        $this->messageProducer->expects(self::never())
            ->method('send');

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessFiltersWebhooksWithoutViewPermission(): void
    {
        $message = $this->createMessage('msg_acl', [
            'topic' => 'product.updated',
            'event_data' => ['id' => 10],
            'timestamp' => 1234567600,
            'entity_class' => 'App\Entity\Secure',
            'entity_id' => 10,
            'message_id' => 'test-integrity-id-acl',
        ]);

        $entity = new \stdClass();
        $entity->id = 10;

        $webhook1 = $this->createWebhook(1);
        $webhook2 = $this->createWebhook(2);

        $repository = $this->createMock(WebhookProducerSettingsRepository::class);
        $repository->expects(self::once())
            ->method('getActiveWebhooks')
            ->with('product.updated')
            ->willReturn([$webhook1, $webhook2]);

        $entityRepository = $this->createMock(ObjectRepository::class);
        $entityRepository->expects(self::once())
            ->method('find')
            ->with(10)
            ->willReturn($entity);

        $this->registry->expects(self::exactly(2))
            ->method('getRepository')
            ->willReturnCallback(function ($class) use ($entityRepository, $repository) {
                return match ($class) {
                    'App\Entity\Secure' => $entityRepository,
                    default => $repository,
                };
            });

        $originalToken = $this->createMock(TokenInterface::class);
        $this->tokenStorage->expects(self::atLeastOnce())
            ->method('getToken')
            ->willReturn($originalToken);

        $this->tokenStorage->expects(self::atLeastOnce())
            ->method('setToken');

        $this->authorizationChecker->expects(self::exactly(2))
            ->method('isGranted')
            ->with('VIEW', $entity)
            ->willReturnOnConsecutiveCalls(true, false);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('info')
            ->with(
                'Webhook ID {webhook_id} was skipped because of insufficient permissions',
                [
                    'webhook_id' => '2',
                    'topic' => 'product.updated',
                    'message_id' => 'test-integrity-id-acl',
                ]
            );
        $this->processor->setLogger($logger);

        $childJob = $this->setupJobRunnerMock($message);
        $this->jobRunner->expects(self::once())
            ->method('createDelayed')
            ->willReturnCallback(function ($name, $closure) use ($childJob) {
                return $closure($this->jobRunner, $childJob);
            });

        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(
                ProcessSingleWebhookNotificationTopic::getName(),
                self::callback(function ($body) {
                    return $body['webhook_id'] === '1';
                })
            );

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessLogsAndAcksWhenAllWebhooksFilteredByAcl(): void
    {
        $message = $this->createMessage('msg_acl_all', [
            'topic' => 'product.updated',
            'event_data' => ['id' => 10],
            'timestamp' => 1234567600,
            'entity_class' => 'App\Entity\Secure',
            'entity_id' => 10,
            'message_id' => 'test-integrity-id-acl-all',
        ]);

        $entity = new \stdClass();
        $entity->id = 10;

        $webhook1 = $this->createWebhook(1);
        $webhook2 = $this->createWebhook(2);

        $repository = $this->createMock(WebhookProducerSettingsRepository::class);
        $repository->expects(self::once())
            ->method('getActiveWebhooks')
            ->with('product.updated')
            ->willReturn([$webhook1, $webhook2]);

        $entityRepository = $this->createMock(ObjectRepository::class);
        $entityRepository->expects(self::once())
            ->method('find')
            ->with(10)
            ->willReturn($entity);

        $this->registry->expects(self::exactly(2))
            ->method('getRepository')
            ->willReturnCallback(function ($class) use ($entityRepository, $repository) {
                return match ($class) {
                    'App\Entity\Secure' => $entityRepository,
                    default => $repository,
                };
            });

        $originalToken = $this->createMock(TokenInterface::class);
        $this->tokenStorage->expects(self::atLeastOnce())
            ->method('getToken')
            ->willReturn($originalToken);
        $this->tokenStorage->expects(self::atLeastOnce())
            ->method('setToken');

        // Both webhooks fail the ACL check
        $this->authorizationChecker->expects(self::exactly(2))
            ->method('isGranted')
            ->with('VIEW', $entity)
            ->willReturn(false);

        // Capture every info() call in order
        $loggedInfoCalls = [];
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::exactly(3))
            ->method('info')
            ->willReturnCallback(
                static function (string $msg, array $ctx) use (&$loggedInfoCalls): void {
                    $loggedInfoCalls[] = ['message' => $msg, 'context' => $ctx];
                }
            );
        $this->processor->setLogger($logger);

        $this->setupJobRunnerMock($message);
        $this->messageProducer->expects(self::never())
            ->method('send');

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);

        // Both per-webhook "skipped" info messages fire …
        self::assertSame(
            'Webhook ID {webhook_id} was skipped because of insufficient permissions',
            $loggedInfoCalls[0]['message']
        );
        self::assertSame('1', $loggedInfoCalls[0]['context']['webhook_id']);
        self::assertSame('product.updated', $loggedInfoCalls[0]['context']['topic']);
        self::assertSame('test-integrity-id-acl-all', $loggedInfoCalls[0]['context']['message_id']);

        self::assertSame(
            'Webhook ID {webhook_id} was skipped because of insufficient permissions',
            $loggedInfoCalls[1]['message']
        );
        self::assertSame('2', $loggedInfoCalls[1]['context']['webhook_id']);

        // … followed by the "no applicable webhooks" info message
        self::assertSame(
            'No applicable active webhooks found for the given topic',
            $loggedInfoCalls[2]['message']
        );
        self::assertSame('product.updated', $loggedInfoCalls[2]['context']['topic']);
        self::assertSame(['id' => 10], $loggedInfoCalls[2]['context']['event_data']);
        self::assertSame('test-integrity-id-acl-all', $loggedInfoCalls[2]['context']['message_id']);
    }

    public function testProcessCreatesDelayedJobsForEachWebhook(): void
    {
        $message = $this->createMessage('msg_multi', [
            'topic' => 'product.created',
            'event_data' => ['data' => 'test'],
            'timestamp' => 1234567000,
            'entity_class' => null,
            'entity_id' => null,
            'message_id' => 'test-integrity-id-multi',
        ]);

        $webhook1 = $this->createWebhook(10);
        $webhook2 = $this->createWebhook(20);
        $webhook3 = $this->createWebhook(30);

        $repository = $this->createMock(WebhookProducerSettingsRepository::class);
        $repository->expects(self::once())
            ->method('getActiveWebhooks')
            ->willReturn([$webhook1, $webhook2, $webhook3]);

        $this->registry->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);

        $rootJob = $this->createMock(Job::class);
        $rootJob->expects(self::any())
            ->method('getId')
            ->willReturn(4242);
        $job = $this->createMock(Job::class);
        $job->expects(self::any())
            ->method('getRootJob')
            ->willReturn($rootJob);
        $childJob = $this->createMock(Job::class);
        $childJob->expects(self::any())
            ->method('getId')
            ->willReturn(42);
        $this->jobRunner->expects(self::once())
            ->method('runUniqueByMessage')
            ->willReturnCallback(
                function ($actualMessage, $closure) use ($job, $message) {
                    self::assertSame($actualMessage, $message);

                    return $closure($this->jobRunner, $job);
                }
            );
        $this->jobRunner->expects(self::exactly(3))
            ->method('createDelayed')
            ->willReturnCallback(function ($name, $closure) use ($childJob) {
                return $closure($this->jobRunner, $childJob);
            });

        $sentMessages = [];
        $this->messageProducer->expects(self::exactly(3))
            ->method('send')
            ->willReturnCallback(function ($topic, $body) use (&$sentMessages) {
                $sentMessages[] = $body;
            });

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
        self::assertCount(3, $sentMessages);
        self::assertEquals('10', $sentMessages[0]['webhook_id']);
        self::assertEquals('20', $sentMessages[1]['webhook_id']);
        self::assertEquals('30', $sentMessages[2]['webhook_id']);
    }

    public function testProcessRestoresOriginalTokenAfterCompletion(): void
    {
        $message = $this->createMessage('msg_token', [
            'topic' => 'order.created',
            'event_data' => ['id' => 1],
            'timestamp' => 1234567400,
            'entity_class' => null,
            'entity_id' => null,
            'message_id' => 'test-integrity-id-token',
        ]);

        $webhook = $this->createWebhook(1);

        $repository = $this->createMock(WebhookProducerSettingsRepository::class);
        $repository->expects(self::once())
            ->method('getActiveWebhooks')
            ->willReturn([$webhook]);

        $this->registry->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);

        $originalToken = $this->createMock(TokenInterface::class);
        $this->tokenStorage->expects(self::atLeastOnce())
            ->method('getToken')
            ->willReturn($originalToken);

        $this->tokenStorage->expects(self::atLeastOnce())
            ->method('setToken')
            ->with($originalToken);

        $childJob = $this->setupJobRunnerMock($message);
        $this->jobRunner->expects(self::once())
            ->method('createDelayed')
            ->willReturnCallback(function ($name, $closure) use ($childJob) {
                return $closure($this->jobRunner, $childJob);
            });

        $this->messageProducer->expects(self::once())
            ->method('send');

        $this->processor->process($message, $this->createMock(SessionInterface::class));
    }

    public function testProcessRestoresOriginalTokenOnException(): void
    {
        $message = $this->createMessage('msg_exception', [
            'topic' => 'order.error',
            'event_data' => ['id' => 1],
            'timestamp' => 1234567300,
            'entity_class' => null,
            'entity_id' => null,
            'message_id' => 'test-integrity-id-exception',
        ]);
        $exception = new \RuntimeException('Error');
        $originalToken = $this->createMock(TokenInterface::class);

        $this->tokenStorage->expects(self::atLeastOnce())
            ->method('getToken')
            ->willReturn($originalToken);

        $setTokenCalls = [];
        $this->tokenStorage->expects(self::atLeastOnce())
            ->method('setToken')
            ->willReturnCallback(function ($token) use (&$setTokenCalls, $originalToken) {
                $setTokenCalls[] = $token;
            });

        $this->registry->expects(self::once())
            ->method('getRepository')
            ->willThrowException($exception);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with(
                'Failed to process webhook notification message',
                self::callback(function ($context) use ($exception) {
                    return isset($context['message'])
                        && isset($context['exception'])
                        && $context['exception'] === $exception;
                })
            );
        $this->processor->setLogger($logger);

        $this->setupJobRunnerMock($message);

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertContains($originalToken, $setTokenCalls);
        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    private function setupJobRunnerMock(Message $message): Job&MockObject
    {
        $rootJob = $this->createMock(Job::class);
        $rootJob->expects(self::any())
            ->method('getId')
            ->willReturn(4242);

        $job = $this->createMock(Job::class);
        $job->expects(self::any())
            ->method('getRootJob')
            ->willReturn($rootJob);

        $childJob = $this->createMock(Job::class);
        $childJob->expects(self::any())
            ->method('getId')
            ->willReturn(42);

        $this->jobRunner->expects(self::once())
            ->method('runUniqueByMessage')
            ->willReturnCallback(
                function ($actualMessage, $closure) use ($job, $message) {
                    self::assertSame($actualMessage, $message);

                    return $closure($this->jobRunner, $job);
                }
            );

        return $childJob;
    }

    private function createWebhook(int $id): WebhookProducerSettings
    {
        return $this->createWebhookWithOwnership($id, true, true);
    }

    private function createWebhookWithOwnership(
        int $id,
        bool $hasOwner,
        bool $hasOrganization
    ): WebhookProducerSettings {
        $webhook = $this->getEntity(WebhookProducerSettings::class, ['id' => (string)$id]);
        if ($hasOwner) {
            $owner = new User();
            $owner->addUserRole(new Role('ROLE_WEBHOOK_' . $id));
            $webhook->setOwner($owner);
        }
        if ($hasOrganization) {
            $webhook->setOrganization(new Organization());
        }

        return $webhook;
    }

    private function expectTokenCreatedForWebhook(WebhookProducerSettings $webhook): TokenInterface&MockObject
    {
        $token = $this->createMock(TokenInterface::class);
        $this->tokenFactory->expects(self::once())
            ->method('create')
            ->with(
                $webhook->getOwner(),
                'main',
                $webhook->getOrganization(),
                $webhook->getOwner()->getUserRoles()
            )
            ->willReturn($token);

        return $token;
    }

    /**
     * The body is resolved against {@see SendWebhookNotificationTopic} before the processor is invoked,
     * so every optional entity option is defaulted and always present in the body the processor receives.
     */
    private function createMessage(string $messageId, array $body): Message
    {
        $message = new Message();
        $message->setMessageId($messageId);
        $message->setProperties([
            JobAwareTopicInterface::UNIQUE_JOB_NAME => SendWebhookNotificationTopic::getName() . ':' . $messageId
        ]);
        $message->setBody(array_merge(
            [
                'entity_class' => null,
                'entity_id' => null,
                'entity_owner_id' => null,
                'entity_organization_id' => null
            ],
            $body
        ));

        return $message;
    }
}
