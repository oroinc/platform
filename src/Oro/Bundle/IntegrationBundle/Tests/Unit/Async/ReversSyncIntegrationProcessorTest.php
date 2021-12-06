<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Async;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Async\ReversSyncIntegrationProcessor;
use Oro\Bundle\IntegrationBundle\Async\Topics;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Logger\LoggerStrategy;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorInterface;
use Oro\Bundle\IntegrationBundle\Provider\ReverseSyncProcessor;
use Oro\Bundle\IntegrationBundle\Provider\TwoWaySyncConnectorInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Test\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
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

    public function testShouldImplementMessageProcessorInterface()
    {
        $this->assertClassImplements(MessageProcessorInterface::class, ReversSyncIntegrationProcessor::class);
    }

    public function testShouldImplementTopicSubscriberInterface()
    {
        $this->assertClassImplements(TopicSubscriberInterface::class, ReversSyncIntegrationProcessor::class);
    }

    public function testShouldImplementContainerAwareInterface()
    {
        $this->assertClassImplements(ContainerAwareInterface::class, ReversSyncIntegrationProcessor::class);
    }

    public function testShouldSubscribeOnReversSyncIntegrationTopic()
    {
        $this->assertEquals([Topics::REVERS_SYNC_INTEGRATION], ReversSyncIntegrationProcessor::getSubscribedTopics());
    }

    public function testCouldBeConstructedWithExpectedArguments()
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

    public function testRejectAndLogIfMessageBodyMissIntegrationId()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('critical')
            ->with('Invalid message: integration_id and connector should not be empty');
        $processor = new ReversSyncIntegrationProcessor(
            $this->createDoctrineHelper(),
            $this->createReversSyncProcessor(),
            $this->createMock(TypesRegistry::class),
            new JobRunner(),
            $this->createMock(TokenStorageInterface::class),
            $logger
        );

        $message = new Message();
        $message->setBody('[]');

        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        $this->assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testThrowIfMessageBodyInvalidJson()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The malformed json given.');

        $processor = new ReversSyncIntegrationProcessor(
            $this->createDoctrineHelper(),
            $this->createReversSyncProcessor(),
            $this->createMock(TypesRegistry::class),
            new JobRunner(),
            $this->createMock(TokenStorageInterface::class),
            $this->createMock(LoggerInterface::class)
        );

        $message = new Message();
        $message->setBody('[}');

        $session = $this->createMock(SessionInterface::class);
        $processor->process($message, $session);
    }

    public function testRejectAndLogIfMessageBodyMissConnector()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('critical')
            ->with(
                'Invalid message: integration_id and connector should not be empty'
            );
        $processor = new ReversSyncIntegrationProcessor(
            $this->createDoctrineHelper(),
            $this->createReversSyncProcessor(),
            $this->createMock(TypesRegistry::class),
            new JobRunner(),
            $this->createMock(TokenStorageInterface::class),
            $logger
        );

        $message = new Message();
        $message->setBody(JSON::encode(['integration_id' => 'theIntegrationId']));

        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        $this->assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldRejectAndLogIfIntegrationNotExist()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('critical')
            ->with('Integration should exist and be enabled');
        $entityManager = $this->createEntityManager();
        $entityManager->expects($this->once())
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
        $message->setBody(JSON::encode(['integration_id' => 'theIntegrationId', 'connector' => 'connector']));

        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        $this->assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldRejectAndLogIfIntegrationIsNotEnabled()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('critical')
            ->with('Integration should exist and be enabled');
        $integration = new Integration();
        $integration->setEnabled(false);

        $entityManager = $this->createEntityManager();
        $entityManager->expects($this->once())
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
        $message->setBody(JSON::encode(['integration_id' => 'theIntegrationId', 'connector' => 'connector']));

        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        $this->assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testRejectIfConnectionIsNotInstanceOfTwoWaySyncConnector()
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setType('theIntegrationType');

        $entityManager = $this->createEntityManager();
        $entityManager->expects($this->once())
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
        $message->setBody(JSON::encode(['integration_id' => 'theIntegrationId', 'connector' => 'theConnector']));

        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        $this->assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldRunSyncAsUniqueJob()
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setType('theIntegrationType');
        $integration->setOrganization(new Organization());

        $entityManager = $this->createEntityManager();
        $entityManager->expects($this->once())
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
            $this->createMock(TokenStorageInterface::class),
            $this->createMock(LoggerInterface::class)
        );

        $message = new Message();
        $message->setBody(JSON::encode(['integration_id' => 'theIntegrationId', 'connector' => 'theConnector']));
        $message->setMessageId('theMessageId');

        $session = $this->createMock(SessionInterface::class);
        $processor->process($message, $session);

        $uniqueJobs = $jobRunner->getRunUniqueJobs();
        self::assertCount(1, $uniqueJobs);
        self::assertEquals('oro_integration:revers_sync_integration:theIntegrationId', $uniqueJobs[0]['jobName']);
        self::assertEquals('theMessageId', $uniqueJobs[0]['ownerId']);
    }

    public function testShouldPerformReversSyncIfConnectorIsInstanceOfTwoWaySyncInterface()
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setType('theIntegrationType');
        $integration->setOrganization(new Organization());

        $entityManager = $this->createEntityManager();
        $entityManager->expects($this->once())
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
        $message->setBody(JSON::encode(['integration_id' => 'theIntegrationId', 'connector' => 'theConnector']));

        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        $this->assertEquals(MessageProcessorInterface::ACK, $status);
    }

    /**
     * @return ReverseSyncProcessor|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createReversSyncProcessor()
    {
        $reverseSyncProcessor = $this->createMock(ReverseSyncProcessor::class);
        $reverseSyncProcessor->expects($this->any())
            ->method('getLoggerStrategy')
            ->willReturn(new LoggerStrategy());

        return $reverseSyncProcessor;
    }

    /**
     * @return EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createEntityManager()
    {
        $configuration = new Configuration();

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->any())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->any())
            ->method('getConnection')
            ->willReturn($connection);

        return $entityManager;
    }

    private function createDoctrineHelper(EntityManagerInterface $entityManager = null): DoctrineHelper
    {
        $helper = $this->createMock(DoctrineHelper::class);
        $helper->expects($this->any())
            ->method('getEntityManagerForClass')
            ->willReturn($entityManager);

        return $helper;
    }
}
