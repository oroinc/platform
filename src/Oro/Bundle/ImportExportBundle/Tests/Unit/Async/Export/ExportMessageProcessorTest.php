<?php
namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async\Export;

use Psr\Log\LoggerInterface;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Async\Export\ExportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenSerializerInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class ExportMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function messageBodyLoggerCriticalDataProvider()
    {
        return [
            [
                '[ExportMessageProcessor] Got invalid message: ' .
                    '"{"jobName":"name","processorAlias":"alias","userId":1}"',
                ['jobName' => 'name', 'processorAlias' => 'alias', 'userId' => 1],
            ],
            [
                '[ExportMessageProcessor] Got invalid message: "{"jobId":1,"processorAlias":"alias","userId":1}"',
                ['jobId' => 1, 'processorAlias' => 'alias', 'userId' => 1],
            ],
            [
                '[ExportMessageProcessor] Got invalid message: "{"jobId":1,"jobName":"name","userId":1}"',
                ['jobId' => 1, 'jobName' => 'name', 'userId' => 1],
            ],
            [
                '[ExportMessageProcessor] Got invalid message: "{"jobId":1,"jobName":"name","processorAlias":"alias"}"',
                ['jobId' => 1, 'jobName' => 'name', 'processorAlias' => 'alias'],
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
            ->with($this->equalTo($loggerMessage))
        ;

        $message = new NullMessage();
        $message->setBody(json_encode($messageBody));

        $processor = new ExportMessageProcessor(
            $this->createExportHandlerMock(),
            $this->createJobRunnerMock(),
            $this->createDoctrineHelperMock(),
            $this->createTokenStorageInterfaceMock(),
            $logger,
            $this->createJobStorageMock()
        );

        $result = $processor->process($message, $this->createSessionInterfaceMock());

        $this->assertEquals(ExportMessageProcessor::REJECT, $result);
    }

    public function testShouldRejectMessageAndLogCriticalIfSecurityTokenInvalid()
    {
        $logger = $this->createLoggerInterfaceMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('[ExportMessageProcessor] Cannot set security token')
        ;

        $message = new NullMessage();
        $message->setBody(json_encode([
            'jobName' => 'name',
            'processorAlias' => 'alias',
            'userId' => 1,
            'jobId' => 3,
            'securityToken' =>'test',
        ]));

        $processor = new ExportMessageProcessor(
            $this->createExportHandlerMock(),
            $this->createJobRunnerMock(),
            $this->createDoctrineHelperMock(),
            $this->createTokenStorageInterfaceMock(),
            $logger,
            $this->createJobStorageMock()
        );

        $processor->setTokenSerializer($this->createTokenSerializerMock());

        $result = $processor->process($message, $this->createSessionInterfaceMock());

        $this->assertEquals(ExportMessageProcessor::REJECT, $result);
    }

    public function testShouldReturnSubscribedTopics()
    {
        $this->assertEquals(
            [Topics::EXPORT],
            ExportMessageProcessor::getSubscribedTopics()
        );
    }

    public function testShouldRunJobAndACKMessage()
    {
        $token = 'organizationId=1;userId=123;userClass=Oro\Bundle\UserBundle\Entity\User;roles=ROLE_1,ROLE_2';
        $message = new NullMessage();
        $message->setBody(json_encode([
            'jobName' => 'name',
            'processorAlias' => 'alias',
            'securityToken' => $token,
            'jobId' => 3,
        ]));

        $jobRunner = $this->createJobRunnerMock();
        $jobRunner
            ->expects($this->once())
            ->method('runDelayed')
            ->willReturn(true)
            ;

        $processor = new ExportMessageProcessor(
            $this->createExportHandlerMock(),
            $jobRunner,
            $this->createDoctrineHelperMock(),
            $this->createTokenStorageInterfaceMock(),
            $this->createLoggerInterfaceMock(),
            $this->createJobStorageMock()
        );

        $tokenSerializer = $this->createTokenSerializerMock();
        $tokenSerializer
            ->expects($this->once())
            ->method('deserialize')
            ->with($token)
            ->willReturn($this->createTokenInterfaceMock());
        $processor->setTokenSerializer($tokenSerializer);

        $result = $processor->process($message, $this->createSessionInterfaceMock());

        $this->assertEquals(ExportMessageProcessor::ACK, $result);
    }

    public function testShouldProcessedDataAndACKMessage()
    {
        $job = new Job();
        $job->setId(1);
        $token = 'organizationId=1;userId=123;userClass=Oro\Bundle\UserBundle\Entity\User;roles=ROLE_1,ROLE_2';

        $message = new NullMessage();
        $message->setBody(json_encode([
            'jobName' => 'name',
            'processorAlias' => 'alias',
            'securityToken' => $token,
            'jobId' => 1,
            'outputFormat' => 'csv',
            'exportType' => 'test_export',
            'options' => ['ids' => [1, 2]],
        ]));

        $jobRunner = $this->createJobRunnerMock();
        $jobRunner
            ->expects($this->once())
            ->method('runDelayed')
            ->with(1)
            ->will(
                $this->returnCallback(
                    function ($jobId, $callback) use ($jobRunner, $job) {
                        return $callback($jobRunner, $job);
                    }
                )
            );
        $exportHandler = $this->createExportHandlerMock();
        $exportHandler
            ->expects($this->once())
            ->method('getExportResult')
            ->with(
                'name',
                'alias',
                'test_export',
                'csv',
                null,
                ['ids' => [1, 2]]
            )
            ->willReturn([
                'success' => true,
                'readsCount' => 100,
                'errorsCount' => 0,
            ]);

        $logger = $this->createLoggerInterfaceMock();
        $logger
            ->expects($this->once())
            ->method('info')
            ->with('Export result. Success: Yes. ReadsCount: 100. ErrorsCount: 0');

        $jobStorage = $this->createJobStorageMock();
        $jobStorage
            ->expects($this->once())
            ->method('saveJob')
            ->with($job);

        $tokenStorage = $this->createTokenStorageInterfaceMock();
        $tokenStorage
            ->expects($this->once())
            ->method('setToken')
            ->with($this->createTokenInterfaceMock());

        $tokenSerializer = $this->createTokenSerializerMock();
        $tokenSerializer
            ->expects($this->once())
            ->method('deserialize')
            ->with($token)
            ->willReturn($this->createTokenInterfaceMock());

        $processor = new ExportMessageProcessor(
            $exportHandler,
            $jobRunner,
            $this->createDoctrineHelperMock(),
            $tokenStorage,
            $logger,
            $jobStorage
        );
        $processor->setTokenSerializer($tokenSerializer);


        $result = $processor->process($message, $this->createSessionInterfaceMock());

        $this->assertEquals(ExportMessageProcessor::ACK, $result);
    }

    protected function getUser()
    {
        $user = new User();
        $user->setId(1);
        $user->setFirstName('John');
        $organization = new Organization();
        $organization->setId(1);
        $user->setOrganization($organization);

        return $user;
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
     * @return \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface
     */
    private function createTokenStorageInterfaceMock()
    {
        return $this->createMock(TokenStorageInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|JobStorage
     */
    private function createJobStorageMock()
    {
        return $this->createMock(JobStorage::class);
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
    private function createTokenInterfaceMock()
    {
        return $this->createMock(TokenInterface::class);
    }
}
