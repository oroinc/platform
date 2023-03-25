<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Async;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Async\ReversSyncIntegrationProcessor;
use Oro\Bundle\IntegrationBundle\Async\Topic\ReverseSyncIntegrationTopic;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Logger\LoggerStrategy;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorInterface;
use Oro\Bundle\IntegrationBundle\Provider\ReverseSyncProcessor;
use Oro\Bundle\IntegrationBundle\Provider\TwoWaySyncConnectorInterface;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Authentication\Token\IntegrationTokenAwareTestTrait;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Test\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\ClassExtensionTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ReversSyncIntegrationProcessorTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;
    use IntegrationTokenAwareTestTrait;

    public function testShouldImplementMessageProcessorInterface(): void
    {
        $this->assertClassImplements(MessageProcessorInterface::class, ReversSyncIntegrationProcessor::class);
    }

    public function testShouldImplementTopicSubscriberInterface(): void
    {
        $this->assertClassImplements(TopicSubscriberInterface::class, ReversSyncIntegrationProcessor::class);
    }

    public function testShouldImplementContainerAwareInterface(): void
    {
        $this->assertClassImplements(ContainerAwareInterface::class, ReversSyncIntegrationProcessor::class);
    }

    public function testShouldSubscribeOnReversSyncIntegrationTopic(): void
    {
        self::assertEquals(
            [ReverseSyncIntegrationTopic::getName()],
            ReversSyncIntegrationProcessor::getSubscribedTopics()
        );
    }

    public function testCouldBeConstructedWithExpectedArguments(): void
    {
        new ReversSyncIntegrationProcessor(
            $this->createDoctrineHelper(),
            $this->createReversSyncProcessor(),
            $this->createMock(TypesRegistry::class),
            new JobRunner(),
            $this->createMock(TokenStorageInterface::class),
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testShouldRejectAndLogIfIntegrationNotExist(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('critical')
            ->with('Integration should exist and be enabled');
        $entityManager = $this->createEntityManager();
        $entityManager->expects(self::once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn(null);

        $processor = new ReversSyncIntegrationProcessor(
            $this->createDoctrineHelper($entityManager),
            $this->createReversSyncProcessor(),
            $this->createMock(TypesRegistry::class),
            new JobRunner(),
            $this->createMock(TokenStorageInterface::class),
            $logger
        );

        $message = new Message();
        $message->setBody(['integration_id' => 'theIntegrationId', 'connector' => 'connector']);

        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        self::assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldRejectAndLogIfIntegrationIsNotEnabled(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('critical')
            ->with('Integration should exist and be enabled');
        $integration = new Integration();
        $integration->setEnabled(false);

        $entityManager = $this->createEntityManager();
        $entityManager->expects(self::once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration);

        $processor = new ReversSyncIntegrationProcessor(
            $this->createDoctrineHelper($entityManager),
            $this->createReversSyncProcessor(),
            $this->createMock(TypesRegistry::class),
            new JobRunner(),
            $this->createMock(TokenStorageInterface::class),
            $logger
        );

        $message = new Message();
        $message->setBody(['integration_id' => 'theIntegrationId', 'connector' => 'connector']);

        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        self::assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testRejectIfConnectionIsNotInstanceOfTwoWaySyncConnector(): void
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setType('theIntegrationType');

        $entityManager = $this->createEntityManager();
        $entityManager->expects(self::once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration);

        $typeRegistry = $this->createMock(TypesRegistry::class);
        $typeRegistry->expects(self::once())
            ->method('getConnectorType')
            ->with('theIntegrationType', 'theConnector')
            ->willReturn($this->createMock(ConnectorInterface::class));

        $reversSyncProcessor = $this->createReversSyncProcessor();
        $reversSyncProcessor->expects(self::never())
            ->method('process');

        $processor = new ReversSyncIntegrationProcessor(
            $this->createDoctrineHelper($entityManager),
            $reversSyncProcessor,
            $typeRegistry,
            new JobRunner(),
            $this->createMock(TokenStorageInterface::class),
            $this->createMock(LoggerInterface::class)
        );

        $message = new Message();
        $message->setBody(['integration_id' => 'theIntegrationId', 'connector' => 'theConnector']);

        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        self::assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldRunSyncAsUniqueJobEmptyToken(): void
    {
        $this->shouldRunSyncAsUniqueJob($this->createMock(TokenStorageInterface::class));
    }

    public function testShouldRunSyncAsUniqueJobWithToken(): void
    {
        $this->shouldRunSyncAsUniqueJob($this->getTokenStorageMock());
    }

    private function shouldRunSyncAsUniqueJob(TokenStorageInterface $tokenStorage): void
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setType('theIntegrationType');
        $integration->setOrganization(new Organization());

        $entityManager = $this->createEntityManager();
        $entityManager->expects(self::once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration);

        $typeRegistry = $this->createMock(TypesRegistry::class);
        $typeRegistry->expects(self::once())
            ->method('getConnectorType')
            ->willReturn($this->createMock(TwoWaySyncConnectorInterface::class));

        $jobRunner = new JobRunner();

        $processor = new ReversSyncIntegrationProcessor(
            $this->createDoctrineHelper($entityManager),
            $this->createReversSyncProcessor(),
            $typeRegistry,
            $jobRunner,
            $tokenStorage,
            $this->createMock(LoggerInterface::class)
        );

        $message = new Message();
        $message->setBody([
            'integration_id' => 'theIntegrationId',
            'connector' => 'theConnector',
            'connector_parameters' => [],
        ]);
        $message->setMessageId('theMessageId');

        $session = $this->createMock(SessionInterface::class);
        $processor->process($message, $session);

        $uniqueJobs = $jobRunner->getRunUniqueJobs();
        self::assertCount(1, $uniqueJobs);
        self::assertEquals('theMessageId', $uniqueJobs[0]['ownerId']);
    }

    public function testShouldPerformReversSyncIfConnectorIsInstanceOfTwoWaySyncInterface(): void
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setType('theIntegrationType');
        $integration->setOrganization(new Organization());

        $entityManager = $this->createEntityManager();
        $entityManager->expects(self::once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration);

        $typeRegistry = $this->createMock(TypesRegistry::class);
        $typeRegistry->expects(self::once())
            ->method('getConnectorType')
            ->with('theIntegrationType', 'theConnector')
            ->willReturn($this->createMock(TwoWaySyncConnectorInterface::class));

        $processor = new ReversSyncIntegrationProcessor(
            $this->createDoctrineHelper($entityManager),
            $this->createReversSyncProcessor(),
            $typeRegistry,
            new JobRunner(),
            $this->createMock(TokenStorageInterface::class),
            $this->createMock(LoggerInterface::class)
        );

        $message = new Message();
        $message->setBody([
            'integration_id' => 'theIntegrationId',
            'connector' => 'theConnector',
            'connector_parameters' => [],
        ]);

        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        self::assertEquals(MessageProcessorInterface::ACK, $status);
    }

    private function createReversSyncProcessor(): ReverseSyncProcessor|\PHPUnit\Framework\MockObject\MockObject
    {
        $reverseSyncProcessor = $this->createMock(ReverseSyncProcessor::class);
        $reverseSyncProcessor->expects(self::any())
            ->method('getLoggerStrategy')
            ->willReturn(new LoggerStrategy());

        return $reverseSyncProcessor;
    }

    private function createEntityManager(): EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject
    {
        $configuration = new Configuration();

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::any())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::any())
            ->method('getConnection')
            ->willReturn($connection);

        return $entityManager;
    }

    private function createDoctrineHelper(EntityManagerInterface $entityManager = null): DoctrineHelper
    {
        $helper = $this->createMock(DoctrineHelper::class);
        $helper->expects(self::any())
            ->method('getEntityManagerForClass')
            ->willReturn($entityManager);

        return $helper;
    }
}
