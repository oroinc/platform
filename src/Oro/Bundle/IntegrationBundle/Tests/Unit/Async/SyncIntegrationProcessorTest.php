<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Async;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\IntegrationBundle\Async\SyncIntegrationProcessor;
use Oro\Bundle\IntegrationBundle\Async\Topics;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Logger\LoggerStrategy;
use Oro\Bundle\IntegrationBundle\Provider\AbstractSyncProcessor;
use Oro\Bundle\IntegrationBundle\Provider\SyncProcessorInterface;
use Oro\Bundle\IntegrationBundle\Provider\SyncProcessorRegistry;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationToken;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Test\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Oro\Component\Testing\ClassExtensionTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SyncIntegrationProcessorTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementMessageProcessorInterface()
    {
        $this->assertClassImplements(MessageProcessorInterface::class, SyncIntegrationProcessor::class);
    }

    public function testShouldImplementTopicSubscriberInterface()
    {
        $this->assertClassImplements(TopicSubscriberInterface::class, SyncIntegrationProcessor::class);
    }

    public function testShouldImplementContainerAwareInterface()
    {
        $this->assertClassImplements(ContainerAwareInterface::class, SyncIntegrationProcessor::class);
    }

    public function testShouldSubscribeOnSyncIntegrationTopic()
    {
        $this->assertEquals([Topics::SYNC_INTEGRATION], SyncIntegrationProcessor::getSubscribedTopics());
    }

    public function testCouldBeConstructedWithExpectedArguments()
    {
        new SyncIntegrationProcessor(
            $this->createDoctrine(),
            $this->createMock(TokenStorageInterface::class),
            $this->createSyncProcessorRegistry(null),
            new JobRunner(),
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testShouldRejectAndLogMessageBodyMissIntegrationId()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('critical')
            ->with('Invalid message: integration_id is empty');
        $processor = new SyncIntegrationProcessor(
            $this->createDoctrine(),
            $this->createMock(TokenStorageInterface::class),
            $this->createSyncProcessorRegistry(null),
            new JobRunner(),
            $logger
        );

        $message = new Message();
        $message->setBody('[]');

        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        $this->assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldThrowIfMessageBodyInvalidJson()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The malformed json given.');

        $processor = new SyncIntegrationProcessor(
            $this->createDoctrine(),
            $this->createMock(TokenStorageInterface::class),
            $this->createSyncProcessorRegistry(null),
            new JobRunner(),
            $this->createMock(LoggerInterface::class)
        );

        $message = new Message();
        $message->setBody('[}');

        $session = $this->createMock(SessionInterface::class);
        $processor->process($message, $session);
    }

    public function testShouldRejectMessageIfIntegrationNotExist()
    {
        $entityManager = $this->createEntityManager();
        $entityManager->expects($this->once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn(null);

        $processor = new SyncIntegrationProcessor(
            $this->createDoctrine($entityManager),
            $this->createMock(TokenStorageInterface::class),
            $this->createSyncProcessorRegistry(null),
            new JobRunner(),
            $this->createMock(LoggerInterface::class)
        );

        $message = new Message();
        $message->setBody(JSON::encode(['integration_id' => 'theIntegrationId']));

        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        $this->assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldRejectMessageIfIntegrationIsNotEnabled()
    {
        $integration = new Integration();
        $integration->setEnabled(false);

        $entityManager = $this->createEntityManager();
        $entityManager->expects($this->once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration);

        $processor = new SyncIntegrationProcessor(
            $this->createDoctrine($entityManager),
            $this->createMock(TokenStorageInterface::class),
            $this->createSyncProcessorRegistry(null),
            new JobRunner(),
            $this->createMock(LoggerInterface::class)
        );

        $message = new Message();
        $message->setBody(JSON::encode(['integration_id' => 'theIntegrationId']));

        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        $this->assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldRunSyncAsUniqueJob()
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());
        $integration->setTransport($this->createTransport());

        $entityManager = $this->createEntityManager();
        $entityManager->expects($this->once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration);

        $jobRunner = new JobRunner();

        $processor = new SyncIntegrationProcessor(
            $this->createDoctrine($entityManager),
            $this->createMock(TokenStorageInterface::class),
            $this->createSyncProcessorRegistry($this->createSyncProcessor()),
            $jobRunner,
            $this->createMock(LoggerInterface::class)
        );

        $message = new Message();
        $message->setBody(JSON::encode(['integration_id' => 'theIntegrationId']));
        $message->setMessageId('theMessageId');

        $session = $this->createMock(SessionInterface::class);
        $processor->process($message, $session);

        $uniqueJobs = $jobRunner->getRunUniqueJobs();
        self::assertCount(1, $uniqueJobs);
        self::assertEquals('oro_integration:sync_integration:theIntegrationId', $uniqueJobs[0]['jobName']);
        self::assertEquals('theMessageId', $uniqueJobs[0]['ownerId']);
    }

    public function testShouldNotInjectLoggerForNonAbstractProcessor()
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());
        $integration->setTransport($this->createTransport());

        $entityManager = $this->createEntityManager();
        $entityManager->expects($this->once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration);

        $jobRunner = new JobRunner();
        $syncProcessor = $this->createMock(SyncProcessorInterface::class);

        $processor = new SyncIntegrationProcessor(
            $this->createDoctrine($entityManager),
            $this->createMock(TokenStorageInterface::class),
            $this->createSyncProcessorRegistry($syncProcessor),
            $jobRunner,
            $this->createMock(LoggerInterface::class)
        );

        $message = new Message();
        $message->setBody(JSON::encode(['integration_id' => 'theIntegrationId']));
        $message->setMessageId('theMessageId');

        $session = $this->createMock(SessionInterface::class);
        $processor->process($message, $session);

        $uniqueJobs = $jobRunner->getRunUniqueJobs();
        self::assertCount(1, $uniqueJobs);
        self::assertEquals('oro_integration:sync_integration:theIntegrationId', $uniqueJobs[0]['jobName']);
        self::assertEquals('theMessageId', $uniqueJobs[0]['ownerId']);
    }

    public function testShouldInitializeTokenStorageIfTokenMissed()
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());
        $integration->setTransport($this->createTransport());

        $entityManager = $this->createEntityManager();
        $entityManager->expects($this->once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects($this->once())
            ->method('setToken')
            ->with($this->isInstanceOf(OrganizationToken::class));

        $syncProcessorRegistry = $this->createSyncProcessorRegistry($this->createSyncProcessor());

        $processor = new SyncIntegrationProcessor(
            $this->createDoctrine($entityManager),
            $tokenStorage,
            $syncProcessorRegistry,
            new JobRunner(),
            $this->createMock(LoggerInterface::class)
        );

        $message = new Message();
        $message->setBody(JSON::encode(['integration_id' => 'theIntegrationId']));
        $message->setMessageId('someId');

        $session = $this->createMock(SessionInterface::class);
        $processor->process($message, $session);
    }

    public function testShouldRejectMessageIfSyncProcessResultFalse()
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());
        $integration->setTransport($this->createTransport());

        $entityManager = $this->createEntityManager();
        $entityManager->expects($this->once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration);

        $syncProcessor = $this->createSyncProcessor();
        $syncProcessor->expects($this->once())
            ->method('process')
            ->willReturn(false);

        $syncProcessorRegistry = $this->createSyncProcessorRegistry($syncProcessor);

        $processor = new SyncIntegrationProcessor(
            $this->createDoctrine($entityManager),
            $this->createMock(TokenStorageInterface::class),
            $syncProcessorRegistry,
            new JobRunner(),
            $this->createMock(LoggerInterface::class)
        );

        $message = new Message();
        $message->setBody(JSON::encode(['integration_id' => 'theIntegrationId']));
        $message->setMessageId('someId');

        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        $this->assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldAckMessageIfSyncProcessResultTrue()
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());
        $integration->setTransport($this->createTransport());

        $entityManager = $this->createEntityManager();
        $entityManager->expects($this->once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration);

        $syncProcessor = $this->createSyncProcessor();
        $syncProcessor->expects($this->once())
            ->method('process')
            ->willReturn(true);

        $syncProcessorRegistry = $this->createSyncProcessorRegistry($syncProcessor);

        $processor = new SyncIntegrationProcessor(
            $this->createDoctrine($entityManager),
            $this->createMock(TokenStorageInterface::class),
            $syncProcessorRegistry,
            new JobRunner(),
            $this->createMock(LoggerInterface::class)
        );

        $message = new Message();
        $message->setBody(JSON::encode(['integration_id' => 'theIntegrationId']));
        $message->setMessageId('someId');

        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        $this->assertEquals(MessageProcessorInterface::ACK, $status);
    }

    public function testShouldSyncIntegrationWithDefaultOptions()
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());
        $integration->setTransport($this->createTransport());

        $entityManager = $this->createEntityManager();
        $entityManager->expects($this->once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration);

        $syncProcessor = $this->createSyncProcessor();
        $syncProcessor->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($integration), null, [])
            ->willReturn(true);

        $syncProcessorRegistry = $this->createSyncProcessorRegistry($syncProcessor);

        $processor = new SyncIntegrationProcessor(
            $this->createDoctrine($entityManager),
            $this->createMock(TokenStorageInterface::class),
            $syncProcessorRegistry,
            new JobRunner(),
            $this->createMock(LoggerInterface::class)
        );

        $message = new Message();
        $message->setBody(JSON::encode(['integration_id' => 'theIntegrationId']));
        $message->setMessageId('someId');

        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        $this->assertEquals(MessageProcessorInterface::ACK, $status);
    }

    public function testShouldSyncIntegrationWithCustomOptions()
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());
        $integration->setTransport($this->createTransport());

        $entityManager = $this->createEntityManager();
        $entityManager->expects($this->once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration);

        $syncProcessor = $this->createSyncProcessor();
        $syncProcessor->expects($this->once())
            ->method('process')
            ->with(
                $this->identicalTo($integration),
                'theConnection',
                [
                    'foo' => 'fooVal',
                    'force' => true,
                ]
            )
            ->willReturn(true);

        $syncProcessorRegistry = $this->createSyncProcessorRegistry($syncProcessor);

        $processor = new SyncIntegrationProcessor(
            $this->createDoctrine($entityManager),
            $this->createMock(TokenStorageInterface::class),
            $syncProcessorRegistry,
            new JobRunner(),
            $this->createMock(LoggerInterface::class)
        );

        $message = new Message();
        $message->setBody(JSON::encode([
            'integration_id' => 'theIntegrationId',
            'connector' => 'theConnection',
            'connector_parameters' => [
                'foo' => 'fooVal',
                'force' => true,
            ]
        ]));
        $message->setMessageId('someId');

        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        $this->assertEquals(MessageProcessorInterface::ACK, $status);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|EntityManagerInterface
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

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry
     */
    private function createDoctrine(EntityManagerInterface $entityManager = null)
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($entityManager);

        return $doctrine;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|AbstractSyncProcessor
     */
    private function createSyncProcessor()
    {
        $syncProcessor = $this->getMockBuilder(AbstractSyncProcessor::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['process', 'getLoggerStrategy'])
            ->addMethods(['assertValidConnector'])
            ->getMock();
        $syncProcessor->expects($this->any())
            ->method('getLoggerStrategy')
            ->willReturn(new LoggerStrategy());

        return $syncProcessor;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|SyncProcessorRegistry
     */
    private function createSyncProcessorRegistry(?SyncProcessorInterface $syncProcessor)
    {
        $syncProcessorRegistry = $this->createMock(SyncProcessorRegistry::class);
        $syncProcessorRegistry->expects($this->any())
            ->method('getProcessorForIntegration')
            ->willReturn($syncProcessor);

        return $syncProcessorRegistry;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Transport
     */
    private function createTransport()
    {
        $transportMock = $this->createMock(Transport::class);
        $transportMock->expects($this->any())
            ->method('getSettingsBag')
            ->willReturn(new ParameterBag());

        return $transportMock;
    }
}
