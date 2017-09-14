<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Psr\Log\LoggerInterface;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;

use Oro\Bundle\ImportExportBundle\Async\ExportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;
use Oro\Bundle\ImportExportBundle\Async\ImportExportJobSummaryResultService;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\SecurityBundle\Authentication\TokenSerializerInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class ExportMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function messageBodyLoggerCriticalDataProvider()
    {
        $msgStart = '[ExportMessageProcessor] Got invalid message: ';
        return [
            'no jobName' => [
                $msgStart . '"{"processorAlias":"alias","userId":1,"securityToken":"token"}"',
                ['processorAlias' => 'alias', 'userId' => 1, 'securityToken' => 'token'],
            ],
            'no processorAlias' => [
                $msgStart . '"{"jobName":"name","userId":1,"securityToken":"token"}"',
                ['jobName' => 'name', 'userId' => 1, 'securityToken' => 'token'],
            ],
            'no userId' => [
                $msgStart . '"{"jobName":"name","processorAlias":"alias","securityToken":"token"}"',
                ['jobName' => 'name', 'processorAlias' => 'alias', 'securityToken' => 'token'],
            ],
            'no securityToken' => [
                $msgStart . '"{"jobName":"name","processorAlias":"alias","userId":1}"',
                ['jobName' => 'name', 'processorAlias' => 'alias', 'userId' => 1],
            ],
        ];
    }

    /**
     * @dataProvider messageBodyLoggerCriticalDataProvider
     */
    public function testShouldRejectMessageAndLogCriticalIfRequiredParametersAreMissing($loggerMessage, $messageBody)
    {
        $logger = $this->createLoggerInterfaceMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with($loggerMessage);

        $message = new NullMessage();
        $message->setBody(json_encode($messageBody));

        $processor = new ExportMessageProcessor(
            $this->createExportHandlerMock(),
            $this->createJobRunnerMock(),
            $this->createMessageProducerInterfaceMock(),
            $this->createConfigManagerMock(),
            $this->createDoctrineHelperMock(),
            $this->createSecurityFacadeMock(),
            $this->createTokenStorageInterfaceMock(),
            $logger,
            $this->createImportExportJobSummaryResultServiceMock()
        );

        $result = $processor->process($message, $this->createSessionInterfaceMock());

        $this->assertEquals(ExportMessageProcessor::REJECT, $result);
    }

    public function testShouldRejectMessageAndLogCriticalIfUserNotFound()
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn(null);

        $doctrineHelper = $this->createDoctrineHelperMock();
        $doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepository')
            ->with(User::class)
            ->willReturn($repository);

        $logger = $this->createLoggerInterfaceMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('[ExportMessageProcessor] Cannot find user by id "1"');

        $message = new NullMessage();
        $message->setBody(
            json_encode(
                [
                    'jobName' => 'name',
                    'processorAlias' => 'alias',
                    'userId' => 1,
                    'securityToken' => 'token'
                ]
            )
        );

        $processor = new ExportMessageProcessor(
            $this->createExportHandlerMock(),
            $this->createJobRunnerMock(),
            $this->createMessageProducerInterfaceMock(),
            $this->createConfigManagerMock(),
            $doctrineHelper,
            $this->createSecurityFacadeMock(),
            $this->createTokenStorageInterfaceMock(),
            $logger,
            $this->createImportExportJobSummaryResultServiceMock()
        );

        $result = $processor->process($message, $this->createSessionInterfaceMock());

        $this->assertEquals(ExportMessageProcessor::REJECT, $result);
    }

    public function testShouldRejectMessageAndLogCriticalIfCantSetTokenInTokenStorage()
    {
        $user = new User();
        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($user);

        $doctrineHelper = $this->createDoctrineHelperMock();
        $doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepository')
            ->with(User::class)
            ->willReturn($repository);

        $logger = $this->createLoggerInterfaceMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('[ExportMessageProcessor] Cannot set security token in the token storage');

        $token = 'test';
        $message = new NullMessage();
        $message->setBody(
            json_encode(
                [
                    'jobName' => 'name',
                    'processorAlias' => 'alias',
                    'userId' => 1,
                    'securityToken' => $token
                ]
            )
        );

        $processor = new ExportMessageProcessor(
            $this->createExportHandlerMock(),
            $this->createJobRunnerMock(),
            $this->createMessageProducerInterfaceMock(),
            $this->createConfigManagerMock(),
            $doctrineHelper,
            $this->createSecurityFacadeMock(),
            $this->createTokenStorageInterfaceMock(),
            $logger,
            $this->createImportExportJobSummaryResultServiceMock()
        );

        $tokenSerializer = $this->createTokenSerializerInterfaceMock();
        $tokenSerializer
            ->expects($this->once())
            ->method('deserialize')
            ->with($token)
            ->willReturn(null);
        $processor->setTokenSerializer($tokenSerializer);

        $result = $processor->process($message, $this->createSessionInterfaceMock());

        $this->assertEquals(ExportMessageProcessor::REJECT, $result);
    }

    public function testShouldSetTokenInTokenStorage()
    {
        $user = new User();
        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($user);

        $doctrineHelper = $this->createDoctrineHelperMock();
        $doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepository')
            ->with(User::class)
            ->willReturn($repository);

        $token = new UsernamePasswordOrganizationToken(
            $user,
            'test',
            'test',
            new Organization(),
            []
        );
        $message = new NullMessage();
        $message->setBody(
            json_encode(
                [
                    'jobName' => 'name',
                    'processorAlias' => 'alias',
                    'userId' => 1,
                    'securityToken' => $token
                ]
            )
        );

        $tokenStorage = $this->createTokenStorageInterfaceMock();
        $tokenSerializer = $this->createTokenSerializerInterfaceMock();
        $tokenSerializer
            ->expects($this->once())
            ->method('deserialize')
            ->willReturn($token);
        $tokenStorage
            ->expects($this->once())
            ->method('setToken')
            ->with($token)
            ->willReturn($token);

        $jobRunner = $this->createJobRunnerMock();
        $jobRunner
            ->expects($this->once())
            ->method('runUnique')
            ->willReturn(true);

        $processor = new ExportMessageProcessor(
            $this->createExportHandlerMock(),
            $jobRunner,
            $this->createMessageProducerInterfaceMock(),
            $this->createConfigManagerMock(),
            $doctrineHelper,
            $this->createSecurityFacadeMock(),
            $tokenStorage,
            $this->createLoggerInterfaceMock(),
            $this->createImportExportJobSummaryResultServiceMock()
        );
        $processor->setTokenSerializer($tokenSerializer);

        $result = $processor->process($message, $this->createSessionInterfaceMock());
        $this->assertEquals(ExportMessageProcessor::ACK, $result);
    }

    public function testShouldReturnSubscribedTopics()
    {
        $this->assertEquals(
            [Topics::EXPORT],
            ExportMessageProcessor::getSubscribedTopics()
        );
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
    private function createMessageProducerInterfaceMock()
    {
        return $this->createMock(MessageProducerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ConfigManager
     */
    private function createConfigManagerMock()
    {
        return $this->createMock(ConfigManager::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper
     */
    private function createDoctrineHelperMock()
    {
        return $this->createMock(DoctrineHelper::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    private function createLoggerInterfaceMock()
    {
        return $this->createMock(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    private function createSessionInterfaceMock()
    {
        return $this->createMock(SessionInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SecurityFacade
     */
    private function createSecurityFacadeMock()
    {
        return $this->createMock(SecurityFacade::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface
     */
    private function createTokenStorageInterfaceMock()
    {
        return $this->createMock(TokenStorageInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TokenSerializerInterface
     */
    private function createTokenSerializerInterfaceMock()
    {
        return $this->createMock(TokenSerializerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ImportExportJobSummaryResultService
     */
    protected function createImportExportJobSummaryResultServiceMock()
    {
        return $this->createMock(ImportExportJobSummaryResultService::class);
    }
}
