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
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Test\JobRunner;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\Null\NullSession;
use Oro\Component\MessageQueue\Util\JSON;
use Oro\Component\Testing\ClassExtensionTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class ReversSyncIntegrationProcessorTest extends \PHPUnit_Framework_TestCase
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
            $this->createDoctrineHelperStub(),
            $this->createReversSyncProcessorMock(),
            $this->createTypeRegistryMock(),
            new JobRunner(),
            $this->createLoggerMock()
        );
    }

    public function testRejectAndLogIfMessageBodyMissIntegrationId()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('Invalid message: integration_id and connector should not be empty: []')
        ;
        $processor = new ReversSyncIntegrationProcessor(
            $this->createDoctrineHelperStub(),
            $this->createReversSyncProcessorMock(),
            $this->createTypeRegistryMock(),
            new JobRunner(),
            $logger
        );

        $message = new NullMessage();
        $message->setBody('[]');

        $status = $processor->process($message, new NullSession());

        $this->assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage The malformed json given.
     */
    public function testThrowIfMessageBodyInvalidJson()
    {
        $processor = new ReversSyncIntegrationProcessor(
            $this->createDoctrineHelperStub(),
            $this->createReversSyncProcessorMock(),
            $this->createTypeRegistryMock(),
            new JobRunner(),
            $this->createLoggerMock()
        );

        $message = new NullMessage();
        $message->setBody('[}');

        $processor->process($message, new NullSession());
    }

    public function testRejectAndLogIfMessageBodyMissConnector()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with(
                'Invalid message: integration_id and connector should not be empty:'.
                ' {"integration_id":"theIntegrationId"}'
            )
        ;
        $processor = new ReversSyncIntegrationProcessor(
            $this->createDoctrineHelperStub(),
            $this->createReversSyncProcessorMock(),
            $this->createTypeRegistryMock(),
            new JobRunner(),
            $logger
        );

        $message = new NullMessage();
        $message->setBody(JSON::encode(['integration_id' => 'theIntegrationId']));

        $status = $processor->process($message, new NullSession());

        $this->assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldRejectAndLogIfIntegrationNotExist()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('Integration should exist and be enabled: theIntegrationId')
        ;
        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects($this->once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn(null)
        ;

        $doctrineHelperStub = $this->createDoctrineHelperStub($entityManagerMock);

        $processor = new ReversSyncIntegrationProcessor(
            $doctrineHelperStub,
            $this->createReversSyncProcessorMock(),
            $this->createTypeRegistryMock(),
            new JobRunner(),
            $logger
        );

        $message = new NullMessage();
        $message->setBody(JSON::encode(['integration_id' => 'theIntegrationId', 'connector' => 'connector']));

        $status = $processor->process($message, new NullSession());

        $this->assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldRejectAndLogIfIntegrationIsNotEnabled()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('Integration should exist and be enabled: theIntegrationId')
        ;
        $integration = new Integration();
        $integration->setEnabled(false);

        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects($this->once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration);
        ;

        $doctrineHelperStub = $this->createDoctrineHelperStub($entityManagerMock);

        $processor = new ReversSyncIntegrationProcessor(
            $doctrineHelperStub,
            $this->createReversSyncProcessorMock(),
            $this->createTypeRegistryMock(),
            new JobRunner(),
            $logger
        );

        $message = new NullMessage();
        $message->setBody(JSON::encode(['integration_id' => 'theIntegrationId', 'connector' => 'connector']));

        $status = $processor->process($message, new NullSession());

        $this->assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testRejectIfConnectionIsNotInstanceOfTwoWaySyncConnector()
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setType('theIntegrationType');

        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects($this->once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration);
        ;

        $doctrineHelperStub = $this->createDoctrineHelperStub($entityManagerMock);

        $typeRegistryMock = $this->createTypeRegistryMock();
        $typeRegistryMock
            ->expects(self::once())
            ->method('getConnectorType')
            ->with('theIntegrationType', 'theConnector')
            ->willReturn($this->createMock(ConnectorInterface::class));

        $reversSyncProcessorMock = $this->createReversSyncProcessorMock();
        $reversSyncProcessorMock
            ->expects(self::never())
            ->method('process');

        $processor = new ReversSyncIntegrationProcessor(
            $doctrineHelperStub,
            $reversSyncProcessorMock,
            $typeRegistryMock,
            new JobRunner(),
            $this->createLoggerMock()
        );

        $message = new NullMessage();
        $message->setBody(JSON::encode(['integration_id' => 'theIntegrationId', 'connector' => 'theConnector']));

        $status = $processor->process($message, new NullSession());

        $this->assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldRunSyncAsUniqueJob()
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setType('theIntegrationType');

        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects($this->once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration);
        ;

        $doctrineHelperStub = $this->createDoctrineHelperStub($entityManagerMock);

        $typeRegistryMock = $this->createTypeRegistryMock();
        $typeRegistryMock
            ->expects(self::once())
            ->method('getConnectorType')
            ->willReturn($this->createMock(TwoWaySyncConnectorInterface::class));

        $jobRunner = new JobRunner();

        $processor = new ReversSyncIntegrationProcessor(
            $doctrineHelperStub,
            $this->createReversSyncProcessorMock(),
            $typeRegistryMock,
            $jobRunner,
            $this->createLoggerMock()
        );

        $message = new NullMessage();
        $message->setBody(JSON::encode(['integration_id' => 'theIntegrationId', 'connector' => 'theConnector']));
        $message->setMessageId('theMessageId');

        $processor->process($message, new NullSession());

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

        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects($this->once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration)
        ;

        $doctrineHelperStub = $this->createDoctrineHelperStub($entityManagerMock);

        $typeRegistryMock = $this->createTypeRegistryMock();
        $typeRegistryMock
            ->expects(self::once())
            ->method('getConnectorType')
            ->with('theIntegrationType', 'theConnector')
            ->willReturn($this->createMock(TwoWaySyncConnectorInterface::class));

        $processor = new ReversSyncIntegrationProcessor(
            $doctrineHelperStub,
            $this->createReversSyncProcessorMock(),
            $typeRegistryMock,
            new JobRunner(),
            $this->createLoggerMock()
        );

        $message = new NullMessage();
        $message->setBody(JSON::encode(['integration_id' => 'theIntegrationId', 'connector' => 'theConnector']));

        $status = $processor->process($message, new NullSession());

        $this->assertEquals(MessageProcessorInterface::ACK, $status);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TypesRegistry
     */
    private function createTypeRegistryMock()
    {
        return $this->createMock(TypesRegistry::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ReverseSyncProcessor
     */
    private function createReversSyncProcessorMock()
    {
        $reverseSyncProcessor =  $this->createMock(ReverseSyncProcessor::class);
        $reverseSyncProcessor
            ->expects($this->any())
            ->method('getLoggerStrategy')
            ->willReturn(new LoggerStrategy())
        ;

        return $reverseSyncProcessor;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityManagerInterface
     */
    private function createEntityManagerStub()
    {
        $configuration = new Configuration();

        $connectionMock = $this->createMock(Connection::class);
        $connectionMock
            ->expects($this->any())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $entityManagerMock
            ->expects($this->any())
            ->method('getConnection')
            ->willReturn($connectionMock);

        return $entityManagerMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper
     */
    private function createDoctrineHelperStub($entityManager = null)
    {
        $helperMock = $this->createMock(DoctrineHelper::class);
        $helperMock
            ->expects($this->any())
            ->method('getEntityManagerForClass')
            ->willReturn($entityManager);

        return $helperMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject | LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }
}
