<?php
namespace Oro\Bundle\DataGridBundle\Tests\Unit\Async\Export;

use Psr\Log\LoggerInterface;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\DataGridBundle\Async\Export\PreExportMessageProcessor;
use Oro\Bundle\DataGridBundle\Async\Topics;
use Oro\Bundle\DataGridBundle\Handler\ExportHandler;
use Oro\Bundle\DataGridBundle\ImportExport\DatagridExportIdFetcher;
use Oro\Bundle\SecurityBundle\Authentication\TokenSerializerInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class PreExportMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldReturnSubscribedTopics()
    {
        $this->assertEquals(
            [Topics::PRE_EXPORT],
            PreExportMessageProcessor::getSubscribedTopics()
        );
    }

    public function invalidMessageBodyParametersProvider()
    {
        return [
            [
                'Got invalid message: "{"parameters":{"gridName":"name"},"format":"csv"}"',
                ['parameters' => ['gridName' => 'name'], 'format' => 'csv'],
            ],
            [
                'Got invalid message: "{"securityToken":"token","format":"csv"}"',
                ['securityToken' => 'token', 'format' => 'csv'],
            ],
            [
                'Got invalid message: "{"securityToken":"token","parameters":{"gridName":"name"}}"',
                ['securityToken' => 'token', 'parameters' => ['gridName' => 'name']],
            ],
        ];
    }

    /**
     * @dataProvider invalidMessageBodyParametersProvider
     * @param string $loggerMessage
     * @param array $messageBody
     */
    public function testShouldRejectMessageAndLogCriticalIfRequiredParametersAreMissing($loggerMessage, $messageBody)
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with($this->stringContains($loggerMessage))
        ;

        $processor = new PreExportMessageProcessor(
            $this->createExportHandlerMock(),
            $this->createJobRunnerMock(),
            $this->createMessageProducerMock(),
            $this->createDatagridExportIdFetcher(),
            $this->createTokenStorageMock(),
            $logger,
            $this->createDependentJobService(),
            100
        );

        $message = new NullMessage();
        $message->setBody(json_encode($messageBody));

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(PreExportMessageProcessor::REJECT, $result);
    }

    public function testShouldRejectMessageAndLogCriticalIfSecurityTokenCannotBeSet()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with($this->stringContains('Cannot set security token'))
        ;

        $tokenSerializer = $this->createTokenSerializerMock();
        $tokenSerializer
            ->expects($this->once())
            ->method('deserialize')
            ->with($this->equalTo('serialized_security_token'))
            ->willReturn(null)
        ;

        $tokenStorage = $this->createTokenStorageMock();
        $tokenStorage
            ->expects($this->never())
            ->method('setToken')
        ;

        $message = new NullMessage();
        $message->setBody(json_encode([
            'format' => 'csv',
            'batchSize' => 100,
            'parameters' => ['gridName' => 'grid_name'],
            'securityToken' => 'serialized_security_token',
        ]));

        $processor = new PreExportMessageProcessor(
            $this->createExportHandlerMock(),
            $this->createJobRunnerMock(),
            $this->createMessageProducerMock(),
            $this->createDatagridExportIdFetcher(),
            $tokenStorage,
            $logger,
            $this->createDependentJobService(),
            100
        );
        $processor->setTokenSerializer($tokenSerializer);

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(PreExportMessageProcessor::REJECT, $result);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Security token is null
     */
    public function testShouldThrowExceptionIfUserIsNotAuthenticated()
    {
        $token = $this->createTokenMock();

        $tokenSerializer = $this->createTokenSerializerMock();
        $tokenSerializer
            ->expects($this->once())
            ->method('deserialize')
            ->with($this->equalTo('serialized_security_token'))
            ->willReturn($token)
        ;

        $tokenStorage = $this->createTokenStorageMock();
        $tokenStorage
            ->expects($this->once())
            ->method('setToken')
            ->with($this->equalTo($token))
        ;
        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn(null)
        ;

        $message = new NullMessage();
        $message->setBody(json_encode([
            'format' => 'csv',
            'batchSize' => 100,
            'parameters' => ['gridName' => 'grid_name'],
            'securityToken' => 'serialized_security_token',
        ]));

        $processor = new PreExportMessageProcessor(
            $this->createExportHandlerMock(),
            $this->createJobRunnerMock(),
            $this->createMessageProducerMock(),
            $this->createDatagridExportIdFetcher(),
            $tokenStorage,
            $this->createLoggerMock(),
            $this->createDependentJobService(),
            100
        );
        $processor->setTokenSerializer($tokenSerializer);

        $processor->process($message, $this->createSessionMock());
    }

    public function invalidUserTypeProvider()
    {
        $notObject = 'not_object';
        $notUserObject = new \stdClass();
        $userWithoutRequiredMethods = $this->createMock(UserInterface::class);
        $userWithoutGetEmailMethod = $this->createPartialMock(
            UserInterface::class,
            ['getId', 'getRoles', 'getPassword', 'getSalt', 'getUsername', 'eraseCredentials']
        );

        return [
            [$notObject],
            [$notUserObject],
            [$userWithoutRequiredMethods],
            [$userWithoutGetEmailMethod],
        ];
    }

    /**
     * @dataProvider invalidUserTypeProvider
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Not supported user type
     *
     * @param mixed $user
     */
    public function testShouldThrowExceptionIfUserHasNotSupportedType($user)
    {
        $token = $this->createTokenMock();
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $tokenSerializer = $this->createTokenSerializerMock();
        $tokenSerializer
            ->expects($this->once())
            ->method('deserialize')
            ->with($this->equalTo('serialized_security_token'))
            ->willReturn($token)
        ;

        $tokenStorage = $this->createTokenStorageMock();
        $tokenStorage
            ->expects($this->once())
            ->method('setToken')
            ->with($this->equalTo($token))
        ;
        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token)
        ;

        $message = new NullMessage();
        $message->setBody(json_encode([
            'format' => 'csv',
            'batchSize' => 100,
            'parameters' => ['gridName' => 'grid_name'],
            'securityToken' => 'serialized_security_token',
        ]));

        $processor = new PreExportMessageProcessor(
            $this->createExportHandlerMock(),
            $this->createJobRunnerMock(),
            $this->createMessageProducerMock(),
            $this->createDatagridExportIdFetcher(),
            $tokenStorage,
            $this->createLoggerMock(),
            $this->createDependentJobService(),
            100
        );
        $processor->setTokenSerializer($tokenSerializer);

        $processor->process($message, $this->createSessionMock());
    }

    public function testShouldRunUniqueJobIfAllParametersCorrect()
    {
        $user = new User();
        $user->setId(1);

        $token = $this->createTokenMock();
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $tokenSerializer = $this->createTokenSerializerMock();
        $tokenSerializer
            ->expects($this->once())
            ->method('deserialize')
            ->with($this->equalTo('serialized_security_token'))
            ->willReturn($token)
        ;

        $tokenStorage = $this->createTokenStorageMock();
        $tokenStorage
            ->expects($this->once())
            ->method('setToken')
            ->with($this->equalTo($token))
        ;
        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token)
        ;

        $jobRunner = $this->createJobRunnerMock();
        $jobRunner
            ->expects($this->once())
            ->method('runUnique')
            ->with(
                $this->equalTo('abcd'),
                $this->equalTo('oro_datagrid.pre_export.grid_name.user_1.csv')
            )
            ->willReturn(true)
        ;

        $message = new NullMessage();
        $message->setMessageId('abcd');
        $message->setBody(json_encode([
            'format' => 'csv',
            'batchSize' => 100,
            'parameters' => ['gridName' => 'grid_name'],
            'securityToken' => 'serialized_security_token',
        ]));

        $processor = new PreExportMessageProcessor(
            $this->createExportHandlerMock(),
            $jobRunner,
            $this->createMessageProducerMock(),
            $this->createDatagridExportIdFetcher(),
            $tokenStorage,
            $this->createLoggerMock(),
            $this->createDependentJobService(),
            100
        );
        $processor->setTokenSerializer($tokenSerializer);

        $processor->process($message, $this->createSessionMock());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->createMock(SessionInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ExportHandler
     */
    private function createExportHandlerMock()
    {
        return $this->createMock(ExportHandler::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|JobRunner
     */
    private function createJobRunnerMock()
    {
        return $this->createMock(JobRunner::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProducerInterface
     */
    private function createMessageProducerMock()
    {
        return $this->createMock(MessageProducerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DatagridExportIdFetcher
     */
    private function createDatagridExportIdFetcher()
    {
        return $this->createMock(DatagridExportIdFetcher::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface
     */
    private function createTokenStorageMock()
    {
        return $this->createMock(TokenStorageInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DependentJobService
     */
    private function createDependentJobService()
    {
        return $this->createMock(DependentJobService::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TokenSerializerInterface
     */
    private function createTokenSerializerMock()
    {
        return $this->createMock(TokenSerializerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TokenInterface
     */
    private function createTokenMock()
    {
        return $this->createMock(TokenInterface::class);
    }
}
