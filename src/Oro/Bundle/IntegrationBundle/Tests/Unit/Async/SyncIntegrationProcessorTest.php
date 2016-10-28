<?php
namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Async;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\IntegrationBundle\Async\SyncIntegrationProcessor;
use Oro\Bundle\IntegrationBundle\Async\Topics;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Provider\AbstractSyncProcessor;
use Oro\Bundle\IntegrationBundle\Provider\SyncProcessorRegistry;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\ConsoleToken;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Test\JobRunner;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\Null\NullSession;
use Oro\Component\MessageQueue\Util\JSON;
use Oro\Component\Testing\ClassExtensionTrait;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class SyncIntegrationProcessorTest extends \PHPUnit_Framework_TestCase
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
            $this->createRegistryStub(),
            $this->createTokenStorageMock(),
            $this->createSyncProcessorRegistryStub(null),
            new JobRunner(),
            $this->createLoggerMock()
        );
    }

    public function testRejectAndLogMessageBodyMissIntegrationId()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('Invalid message: integration_id is empty')
        ;
        $processor = new SyncIntegrationProcessor(
            $this->createRegistryStub(),
            $this->createTokenStorageMock(),
            $this->createSyncProcessorRegistryStub(null),
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
        $processor = new SyncIntegrationProcessor(
            $this->createRegistryStub(),
            $this->createTokenStorageMock(),
            $this->createSyncProcessorRegistryStub(null),
            new JobRunner(),
            $this->createLoggerMock()
        );

        $message = new NullMessage();
        $message->setBody('[}');

        $processor->process($message, new NullSession());
    }
    
    public function testShouldRejectMessageIfIntegrationNotExist()
    {
        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects($this->once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn(null);
        ;

        $registryStub = $this->createRegistryStub($entityManagerMock);

        $processor = new SyncIntegrationProcessor(
            $registryStub,
            $this->createTokenStorageMock(),
            $this->createSyncProcessorRegistryStub(null),
            new JobRunner(),
            $this->createLoggerMock()
        );

        $message = new NullMessage();
        $message->setBody(JSON::encode(['integration_id' => 'theIntegrationId']));

        $status = $processor->process($message, new NullSession());

        $this->assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldRejectMessageIfIntegrationIsNotEnabled()
    {
        $integration = new Integration();
        $integration->setEnabled(false);

        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects($this->once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration);
        ;

        $registryStub = $this->createRegistryStub($entityManagerMock);

        $processor = new SyncIntegrationProcessor(
            $registryStub,
            $this->createTokenStorageMock(),
            $this->createSyncProcessorRegistryStub(null),
            new JobRunner(),
            $this->createLoggerMock()
        );

        $message = new NullMessage();
        $message->setBody(JSON::encode(['integration_id' => 'theIntegrationId']));

        $status = $processor->process($message, new NullSession());

        $this->assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldRunSyncAsUniqueJob()
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());
        $integration->setTransport($this->createTransportStub());

        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects($this->once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration);
        ;

        $jobRunner = new JobRunner();

        $processor = new SyncIntegrationProcessor(
            $this->createRegistryStub($entityManagerMock),
            $this->createTokenStorageMock(),
            $this->createSyncProcessorRegistryStub($this->createSyncProcessorMock()),
            $jobRunner,
            $this->createLoggerMock()
        );

        $message = new NullMessage();
        $message->setBody(JSON::encode(['integration_id' => 'theIntegrationId']));
        $message->setMessageId('theMessageId');

        $processor->process($message, new NullSession());

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
        $integration->setTransport($this->createTransportStub());

        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects($this->once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration);
        ;

        $registryStub = $this->createRegistryStub($entityManagerMock);

        $tokenStorageStub = $this->createTokenStorageMock();
        $tokenStorageStub
            ->expects($this->once())
            ->method('setToken')
            ->with($this->isInstanceOf(ConsoleToken::class))
        ;

        $syncProcessorRegistryStub = $this->createSyncProcessorRegistryStub($this->createSyncProcessorMock());

        $processor = new SyncIntegrationProcessor(
            $registryStub,
            $tokenStorageStub,
            $syncProcessorRegistryStub,
            new JobRunner(),
            $this->createLoggerMock()
        );

        $message = new NullMessage();
        $message->setBody(JSON::encode(['integration_id' => 'theIntegrationId']));
        $message->setMessageId('someId');

        $processor->process($message, new NullSession());
    }

    public function testShouldNotInitializeTokenStorageIfTokenAlreadySet()
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());
        $integration->setTransport($this->createTransportStub());

        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects($this->once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration);
        ;

        $registryStub = $this->createRegistryStub($entityManagerMock);

        $tokenStorageStub = $this->createTokenStorageMock();
        $tokenStorageStub
            ->expects($this->once())
            ->method('getToken')
            ->willReturn(new ConsoleToken())
        ;
        $tokenStorageStub
            ->expects($this->never())
            ->method('setToken')
        ;

        $syncProcessorRegistryStub = $this->createSyncProcessorRegistryStub($this->createSyncProcessorMock());

        $processor = new SyncIntegrationProcessor(
            $registryStub,
            $tokenStorageStub,
            $syncProcessorRegistryStub,
            new JobRunner(),
            $this->createLoggerMock()
        );

        $message = new NullMessage();
        $message->setBody(JSON::encode(['integration_id' => 'theIntegrationId']));
        $message->setMessageId('someId');

        $processor->process($message, new NullSession());
    }

    public function testShouldRejectMessageIfSyncProcessResultFalse()
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());
        $integration->setTransport($this->createTransportStub());

        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects($this->once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration);
        ;

        $registryStub = $this->createRegistryStub($entityManagerMock);

        $syncProcessorMock = $this->createSyncProcessorMock();
        $syncProcessorMock
            ->expects($this->once())
            ->method('process')
            ->willReturn(false)
        ;

        $syncProcessorRegistryStub = $this->createSyncProcessorRegistryStub($syncProcessorMock);

        $processor = new SyncIntegrationProcessor(
            $registryStub,
            $this->createTokenStorageMock(),
            $syncProcessorRegistryStub,
            new JobRunner(),
            $this->createLoggerMock()
        );

        $message = new NullMessage();
        $message->setBody(JSON::encode(['integration_id' => 'theIntegrationId']));
        $message->setMessageId('someId');

        $status = $processor->process($message, new NullSession());

        $this->assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldAckMessageIfSyncProcessResultTrue()
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());
        $integration->setTransport($this->createTransportStub());

        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects($this->once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration);
        ;

        $registryStub = $this->createRegistryStub($entityManagerMock);

        $syncProcessorMock = $this->createSyncProcessorMock();
        $syncProcessorMock
            ->expects($this->once())
            ->method('process')
            ->willReturn(true)
        ;

        $syncProcessorRegistryStub = $this->createSyncProcessorRegistryStub($syncProcessorMock);

        $processor = new SyncIntegrationProcessor(
            $registryStub,
            $this->createTokenStorageMock(),
            $syncProcessorRegistryStub,
            new JobRunner(),
            $this->createLoggerMock()
        );

        $message = new NullMessage();
        $message->setBody(JSON::encode(['integration_id' => 'theIntegrationId']));
        $message->setMessageId('someId');

        $status = $processor->process($message, new NullSession());

        $this->assertEquals(MessageProcessorInterface::ACK, $status);
    }

    public function testShouldSyncIntegrationWithDefaultOptions()
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());
        $integration->setTransport($this->createTransportStub());

        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects($this->once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration);
        ;

        $registryStub = $this->createRegistryStub($entityManagerMock);

        $syncProcessorMock = $this->createSyncProcessorMock();
        $syncProcessorMock
            ->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($integration), null, [])
            ->willReturn(true)
        ;

        $syncProcessorRegistryStub = $this->createSyncProcessorRegistryStub($syncProcessorMock);

        $processor = new SyncIntegrationProcessor(
            $registryStub,
            $this->createTokenStorageMock(),
            $syncProcessorRegistryStub,
            new JobRunner(),
            $this->createLoggerMock()
        );

        $message = new NullMessage();
        $message->setBody(JSON::encode(['integration_id' => 'theIntegrationId']));
        $message->setMessageId('someId');

        $status = $processor->process($message, new NullSession());

        $this->assertEquals(MessageProcessorInterface::ACK, $status);
    }

    public function testShouldSyncIntegrationWithCustomOptions()
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());
        $integration->setTransport($this->createTransportStub());

        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects($this->once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration);
        ;

        $registryStub = $this->createRegistryStub($entityManagerMock);

        $syncProcessorMock = $this->createSyncProcessorMock();
        $syncProcessorMock
            ->expects($this->once())
            ->method('process')
            ->with(
                $this->identicalTo($integration),
                'theConnection',
                [
                    'foo' => 'fooVal',
                    'force' => true,
                ]
            )
            ->willReturn(true)
        ;

        $syncProcessorRegistryStub = $this->createSyncProcessorRegistryStub($syncProcessorMock);

        $processor = new SyncIntegrationProcessor(
            $registryStub,
            $this->createTokenStorageMock(),
            $syncProcessorRegistryStub,
            new JobRunner(),
            $this->createLoggerMock()
        );

        $message = new NullMessage();
        $message->setBody(JSON::encode([
            'integration_id' => 'theIntegrationId',
            'connector' => 'theConnection',
            'connector_parameters' => [
                'foo' => 'fooVal',
                'force' => true,
            ]
        ]));
        $message->setMessageId('someId');

        $status = $processor->process($message, new NullSession());

        $this->assertEquals(MessageProcessorInterface::ACK, $status);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityManagerInterface
     */
    private function createEntityManagerStub()
    {
        $configuration = new Configuration();

        $connectionMock = $this->getMock(Connection::class, [], [], '', false);
        $connectionMock
            ->expects($this->any())
            ->method('getConfiguration')
            ->willReturn($configuration)
        ;

        $entityManagerMock = $this->getMock(EntityManagerInterface::class);
        $entityManagerMock
            ->expects($this->any())
            ->method('getConnection')
            ->willReturn($connectionMock)
        ;

        return $entityManagerMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|RegistryInterface
     */
    private function createRegistryStub($entityManager = null)
    {
        $registryMock = $this->getMock(RegistryInterface::class);
        $registryMock
            ->expects($this->any())
            ->method('getManager')
            ->willReturn($entityManager)
        ;

        return $registryMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AbstractSyncProcessor
     */
    private function createSyncProcessorMock()
    {
        return $this->getMock(AbstractSyncProcessor::class, ['process'], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SyncProcessorRegistry
     */
    private function createSyncProcessorRegistryStub($syncProcessor)
    {
        $syncProcessorRegistry = $this->getMock(SyncProcessorRegistry::class);
        $syncProcessorRegistry
            ->expects($this->any())
            ->method('getProcessorForIntegration')
            ->willReturn($syncProcessor)
        ;

        return $syncProcessorRegistry;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface
     */
    private function createTokenStorageMock()
    {
        return $this->getMock(TokenStorageInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Transport
     */
    private function createTransportStub()
    {
        $transportMock = $this->getMock(Transport::class);
        $transportMock
            ->expects($this->any())
            ->method('getSettingsBag')
            ->willReturn(new ParameterBag())
        ;

        return $transportMock;
    }


    /**
     * @return \PHPUnit_Framework_MockObject_MockObject | LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->getMock(LoggerInterface::class);
    }
}
