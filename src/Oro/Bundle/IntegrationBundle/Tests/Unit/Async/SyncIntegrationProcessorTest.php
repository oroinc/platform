<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Async;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\IntegrationBundle\Async\SyncIntegrationProcessor;
use Oro\Bundle\IntegrationBundle\Async\Topic\SyncIntegrationTopic;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Logger\LoggerStrategy;
use Oro\Bundle\IntegrationBundle\Provider\AbstractSyncProcessor;
use Oro\Bundle\IntegrationBundle\Provider\SyncProcessorInterface;
use Oro\Bundle\IntegrationBundle\Provider\SyncProcessorRegistry;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Authentication\Token\IntegrationTokenAwareTestTrait;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationToken;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Test\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\ClassExtensionTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SyncIntegrationProcessorTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;
    use IntegrationTokenAwareTestTrait;

    public function testShouldImplementMessageProcessorInterface(): void
    {
        $this->assertClassImplements(MessageProcessorInterface::class, SyncIntegrationProcessor::class);
    }

    public function testShouldImplementTopicSubscriberInterface(): void
    {
        $this->assertClassImplements(TopicSubscriberInterface::class, SyncIntegrationProcessor::class);
    }

    public function testShouldImplementContainerAwareInterface(): void
    {
        $this->assertClassImplements(ContainerAwareInterface::class, SyncIntegrationProcessor::class);
    }

    public function testShouldSubscribeOnSyncIntegrationTopic(): void
    {
        self::assertEquals([SyncIntegrationTopic::getName()], SyncIntegrationProcessor::getSubscribedTopics());
    }

    public function testCouldBeConstructedWithExpectedArguments(): void
    {
        new SyncIntegrationProcessor(
            $this->createDoctrine(),
            $this->createMock(TokenStorageInterface::class),
            $this->createSyncProcessorRegistry(null),
            new JobRunner(),
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testShouldRejectMessageIfIntegrationNotExist(): void
    {
        $entityManager = $this->createEntityManager();
        $entityManager->expects(self::once())
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
        $message->setBody(['integration_id' => 'theIntegrationId']);

        $status = $processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldRejectMessageIfIntegrationIsNotEnabled(): void
    {
        $integration = new Integration();
        $integration->setEnabled(false);

        $entityManager = $this->createEntityManager();
        $entityManager->expects(self::once())
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
        $message->setBody(['integration_id' => 'theIntegrationId']);

        $status = $processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldRunSyncAsUniqueJob(): void
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());
        $integration->setTransport($this->createTransport());

        $entityManager = $this->createEntityManager();
        $entityManager->expects(self::once())
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
        $message->setMessageId('theMessageId');
        $message->setBody([
            'integration_id' => 'theIntegrationId',
            'connector' => 'theConnection',
            'connector_parameters' => [],
            'transport_batch_size' => 100,
        ]);

        $processor->process($message, $this->createMock(SessionInterface::class));

        $uniqueJobs = $jobRunner->getRunUniqueJobs();
        self::assertCount(1, $uniqueJobs);
        self::assertEquals('theMessageId', $uniqueJobs[0]['ownerId']);
    }

    public function testShouldNotInjectLoggerForNonAbstractProcessor(): void
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());
        $integration->setTransport($this->createTransport());

        $entityManager = $this->createEntityManager();
        $entityManager->expects(self::once())
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
        $message->setBody([
            'integration_id' => 'theIntegrationId',
            'connector' => 'theConnection',
            'connector_parameters' => [],
            'transport_batch_size' => 100,
        ]);
        $message->setMessageId('theMessageId');

        $processor->process($message, $this->createMock(SessionInterface::class));

        $uniqueJobs = $jobRunner->getRunUniqueJobs();
        self::assertCount(1, $uniqueJobs);
        self::assertEquals('theMessageId', $uniqueJobs[0]['ownerId']);
    }

    public function testShouldInitializeTokenStorageIfTokenMissed(): void
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());
        $integration->setTransport($this->createTransport());

        $entityManager = $this->createEntityManager();
        $entityManager->expects(self::once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration);

        $syncProcessorRegistry = $this->createSyncProcessorRegistry($this->createSyncProcessor());

        $processor = new SyncIntegrationProcessor(
            $this->createDoctrine($entityManager),
            $this->getTokenStorageMock(),
            $syncProcessorRegistry,
            new JobRunner(),
            $this->createMock(LoggerInterface::class)
        );

        $message = new Message();
        $message->setBody([
            'integration_id' => 'theIntegrationId',
            'connector' => 'theConnection',
            'connector_parameters' => [],
            'transport_batch_size' => 100,
        ]);
        $message->setMessageId('someId');

        $processor->process($message, $this->createMock(SessionInterface::class));
    }

    public function testShouldRejectMessageIfSyncProcessResultFalse(): void
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());
        $integration->setTransport($this->createTransport());

        $entityManager = $this->createEntityManager();
        $entityManager->expects(self::once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration);

        $syncProcessor = $this->createSyncProcessor();
        $syncProcessor->expects(self::once())
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
        $message->setBody([
            'integration_id' => 'theIntegrationId',
            'connector' => 'theConnection',
            'connector_parameters' => [],
            'transport_batch_size' => 100,
        ]);
        $message->setMessageId('someId');

        $status = $processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldAckMessageIfSyncProcessResultTrue(): void
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());
        $integration->setTransport($this->createTransport());

        $entityManager = $this->createEntityManager();
        $entityManager->expects(self::once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration);

        $syncProcessor = $this->createSyncProcessor();
        $syncProcessor->expects(self::once())
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
        $message->setBody([
            'integration_id' => 'theIntegrationId',
            'connector' => 'theConnection',
            'connector_parameters' => [],
            'transport_batch_size' => 100,
        ]);
        $message->setMessageId('someId');

        $status = $processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $status);
    }

    public function testShouldSyncIntegrationWithCustomOptions(): void
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());
        $integration->setTransport($this->createTransport());

        $entityManager = $this->createEntityManager();
        $entityManager->expects(self::once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration);

        $syncProcessor = $this->createSyncProcessor();
        $syncProcessor->expects(self::once())
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
        $message->setBody([
            'integration_id' => 'theIntegrationId',
            'connector' => 'theConnection',
            'connector_parameters' => [
                'foo' => 'fooVal',
                'force' => true,
            ],
            'transport_batch_size' => 100,
        ]);
        $message->setMessageId('someId');

        $status = $processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $status);
    }

    /**
     * @dataProvider tokenProvider
     */
    public function testThatCorrectTokenIsPassedToTokenStorage(?OrganizationToken $token)
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());
        $integration->setTransport($this->createTransport());
        $integrationID = 'theIntegrationId';

        $entityManager = $this->createEntityManager();
        $entityManager->expects(self::once())
            ->method('find')
            ->with(Integration::class, $integrationID)
            ->willReturn($integration);

        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($token);

        $message = $this->createMock(Message::class);
        $message->method('getBody')->willReturn([
            'integration_id' => $integrationID,
            'transport_batch_size' => 100,
        ]);

        $processor = new SyncIntegrationProcessor(
            $this->createDoctrine($entityManager),
            $tokenStorage,
            $this->createMock(SyncProcessorRegistry::class),
            $this->createMock(JobRunner::class),
            $this->createMock(LoggerInterface::class)
        );

        $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(
            $integration->getOrganization(),
            $tokenStorage->getToken()->getOrganization(),
        );

        $this->assertEquals(
            'Integration: ' . $integration->getName(),
            $tokenStorage->getToken()->getAttribute('owner_description')
        );
    }

    private function tokenProvider(): array
    {
        return [
            [null],
            [new OrganizationToken(new Organization())],
        ];
    }

    private function createEntityManager(): EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::any())
            ->method('getConfiguration')
            ->willReturn(new Configuration());

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::any())
            ->method('getConnection')
            ->willReturn($connection);

        return $entityManager;
    }

    private function createDoctrine(
        EntityManagerInterface $entityManager = null
    ): ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManager')
            ->willReturn($entityManager);

        return $doctrine;
    }

    private function createSyncProcessor(): AbstractSyncProcessor|\PHPUnit\Framework\MockObject\MockObject
    {
        $syncProcessor = $this->getMockBuilder(AbstractSyncProcessor::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['process', 'getLoggerStrategy'])
            ->addMethods(['assertValidConnector'])
            ->getMock();
        $syncProcessor->expects(self::any())
            ->method('getLoggerStrategy')
            ->willReturn(new LoggerStrategy());

        return $syncProcessor;
    }

    private function createSyncProcessorRegistry(
        ?SyncProcessorInterface $syncProcessor
    ): SyncProcessorRegistry|\PHPUnit\Framework\MockObject\MockObject {
        $syncProcessorRegistry = $this->createMock(SyncProcessorRegistry::class);
        $syncProcessorRegistry->expects(self::any())
            ->method('getProcessorForIntegration')
            ->willReturn($syncProcessor);

        return $syncProcessorRegistry;
    }

    private function createTransport(): Transport|\PHPUnit\Framework\MockObject\MockObject
    {
        $transport = $this->createMock(Transport::class);
        $transport->expects(self::any())
            ->method('getSettingsBag')
            ->willReturn(new ParameterBag());

        return $transport;
    }
}
