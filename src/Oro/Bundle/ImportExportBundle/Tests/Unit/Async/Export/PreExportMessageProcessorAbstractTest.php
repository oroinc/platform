<?php
namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async\Export;

use Oro\Bundle\ImportExportBundle\Async\Export\PreExportMessageProcessorAbstract;
use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\DependentJobContext;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class PreExportMessageProcessorAbstractTest extends \PHPUnit\Framework\TestCase
{
    private const USER_ID = 54;

    public function testMustImplementMessageProcessorAndTopicSubscriberInterfaces()
    {
        $processor = $this->createMock(PreExportMessageProcessorAbstract::class);

        $this->assertInstanceOf(MessageProcessorInterface::class, $processor);
        $this->assertInstanceOf(TopicSubscriberInterface::class, $processor);
    }

    public function testCanBeConstructedWithRequiredAttributes()
    {
        $processor = $this->getMockBuilder(PreExportMessageProcessorAbstract::class)
            ->setConstructorArgs([
                $this->createJobRunnerMock(),
                $this->createMessageProducerMock(),
                $this->createTokenStorageMock(),
                $this->createDependentJobMock(),
                $this->createLoggerMock(),
                100
            ])
            ->setMethods([
                'getJobUniqueName',
                'getExportingEntityIds',
                'getDelayedJobCallback',
                'getMessageBody',
                'getSubscribedTopics'
            ])
            ->getMock()
        ;

        $this->assertInstanceOf(PreExportMessageProcessorAbstract::class, $processor);
    }
    
    public function testShouldRejectMessageIfGetMessageBodyReturnFalse()
    {
        $processor = $this->getMockBuilder(PreExportMessageProcessorAbstract::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getJobUniqueName',
                'getExportingEntityIds',
                'getDelayedJobCallback',
                'getMessageBody',
                'getSubscribedTopics'
            ])
            ->getMock()
        ;

        $processor
            ->expects($this->once())
            ->method('getMessageBody')
            ->willReturn(false)
        ;

        $message = new NullMessage();

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(PreExportMessageProcessorAbstract::REJECT, $result);
    }

    public function uniqueJobResultProvider()
    {
        return [
            [ true, PreExportMessageProcessorAbstract::ACK ],
            [ false, PreExportMessageProcessorAbstract::REJECT ],
        ];
    }

    /**
     * @dataProvider uniqueJobResultProvider
     * @param string $jobResult
     * @param string $expectedResult
     */
    public function testShouldReturnMessageStatusDependsOfJobResult($jobResult, $expectedResult)
    {
        $jobUniqueName = 'job_unique_name';

        $message = new NullMessage();
        $message->setMessageId(123);

        $jobRunner = $this->createJobRunnerMock();
        $jobRunner
            ->expects($this->once())
            ->method('runUnique')
            ->with(
                $this->equalTo($message->getMessageId()),
                $this->equalTo($jobUniqueName)
            )
            ->willReturn($jobResult)
        ;

        $processor = $this->getMockBuilder(PreExportMessageProcessorAbstract::class)
            ->setConstructorArgs([
                $jobRunner,
                $this->createMessageProducerMock(),
                $this->createTokenStorageMock(),
                $this->createDependentJobMock(),
                $this->createLoggerMock(),
                100
            ])
            ->setMethods([
                'getJobUniqueName',
                'getExportingEntityIds',
                'getDelayedJobCallback',
                'getMessageBody',
                'getSubscribedTopics'
            ])
            ->getMock()
        ;
        $processor
            ->expects($this->once())
            ->method('getMessageBody')
            ->willReturn(['message_body'])
        ;
        $processor
            ->expects($this->once())
            ->method('getJobUniqueName')
            ->with($this->equalTo(['message_body']))
            ->willReturn($jobUniqueName)
        ;

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Security token is null
     */
    public function testShouldThrowExceptionOnGetUserIfTokenIsNull()
    {
        $messageBody = ['message_body'];
        $jobUniqueName = 'job_unique_name';
        $message = new NullMessage();
        $message->setMessageId(123);

        $job = $this->createJob(1);
        $childJob = $this->createJob(10, $job);

        $jobRunner = $this->createJobRunnerMock();
        $jobRunner
            ->expects($this->once())
            ->method('runUnique')
            ->with($this->equalTo($message->getMessageId()), $this->equalTo($jobUniqueName))
            ->will($this->returnCallback(function ($jobId, $name, $callback) use ($jobRunner, $childJob) {
                return $callback($jobRunner, $childJob);
            }))
        ;

        $jobRunner
            ->expects($this->once())
            ->method('createDelayed')
            ->with($jobUniqueName.'.chunk.1')
        ;

        $tokenStorage = $this->createTokenStorageMock();
        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn(null)
        ;

        $dependentJobContext = $this->createDependentJobContextMock();

        $dependentJob = $this->createDependentJobMock();
        $dependentJob
            ->expects($this->once())
            ->method('createDependentJobContext')
            ->with($this->equalTo($job))
            ->willReturn($dependentJobContext)
        ;
        $dependentJob
            ->expects($this->never())
            ->method('saveDependentJob')
        ;

        $processor = $this->getMockBuilder(PreExportMessageProcessorAbstract::class)
            ->setConstructorArgs([
                $jobRunner,
                $this->createMessageProducerMock(),
                $tokenStorage,
                $dependentJob,
                $this->createLoggerMock(),
                100
            ])
            ->setMethods([
                'getJobUniqueName',
                'getExportingEntityIds',
                'getDelayedJobCallback',
                'getMessageBody',
                'getSubscribedTopics'
            ])
            ->getMock()
        ;
        $processor
            ->expects($this->once())
            ->method('getMessageBody')
            ->willReturn($messageBody)
        ;
        $processor
            ->expects($this->once())
            ->method('getJobUniqueName')
            ->with($this->equalTo($messageBody))
            ->willReturn($jobUniqueName)
        ;
        $processor
            ->expects($this->once())
            ->method('getExportingEntityIds')
            ->with($this->equalTo($messageBody))
            ->willReturn([])
        ;
        $processor
            ->expects($this->once())
            ->method('getDelayedJobCallback')
            ->with($this->equalTo($messageBody))
            ->willReturn($this->getAnonymousFunction())
        ;

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(PreExportMessageProcessorAbstract::ACK, $result);
    }

    public function invalidUserTypeProvider()
    {
        $notObject = 'not_object';
        $notUserObject = new \stdClass();
        $userWithoutRequiredMethods = $this->createUserMock();
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
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testShouldThrowExceptionOnGetUserIfUserTypeInvalid($user)
    {
        $messageBody = ['message_body'];
        $jobUniqueName = 'job_unique_name';
        $message = new NullMessage();
        $message->setMessageId(123);

        $job = $this->createJob(1);
        $childJob = $this->createJob(10, $job);

        $jobRunner = $this->createJobRunnerMock();
        $jobRunner
            ->expects($this->once())
            ->method('runUnique')
            ->with($this->equalTo($message->getMessageId()), $this->equalTo($jobUniqueName))
            ->will($this->returnCallback(function ($jobId, $name, $callback) use ($jobRunner, $childJob) {
                return $callback($jobRunner, $childJob);
            }))
        ;

        $jobRunner
            ->expects($this->once())
            ->method('createDelayed')
            ->with($jobUniqueName.'.chunk.1')
        ;

        $token = $this->createTokenMock();
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $tokenStorage = $this->createTokenStorageMock();
        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token)
        ;

        $dependentJobContext = $this->createDependentJobContextMock();

        $dependentJob = $this->createDependentJobMock();
        $dependentJob
            ->expects($this->once())
            ->method('createDependentJobContext')
            ->with($this->equalTo($job))
            ->willReturn($dependentJobContext)
        ;
        $dependentJob
            ->expects($this->never())
            ->method('saveDependentJob')
        ;

        $processor = $this->getMockBuilder(PreExportMessageProcessorAbstract::class)
            ->setConstructorArgs([
                $jobRunner,
                $this->createMessageProducerMock(),
                $tokenStorage,
                $dependentJob,
                $this->createLoggerMock(),
                100
            ])
            ->setMethods([
                'getJobUniqueName',
                'getExportingEntityIds',
                'getDelayedJobCallback',
                'getMessageBody',
                'getSubscribedTopics'
            ])
            ->getMock()
        ;
        $processor
            ->expects($this->once())
            ->method('getMessageBody')
            ->willReturn($messageBody)
        ;
        $processor
            ->expects($this->once())
            ->method('getJobUniqueName')
            ->with($this->equalTo($messageBody))
            ->willReturn($jobUniqueName)
        ;
        $processor
            ->expects($this->once())
            ->method('getExportingEntityIds')
            ->with($this->equalTo($messageBody))
            ->willReturn([])
        ;
        $processor
            ->expects($this->once())
            ->method('getDelayedJobCallback')
            ->with($this->equalTo($messageBody))
            ->willReturn($this->getAnonymousFunction())
        ;

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(PreExportMessageProcessorAbstract::ACK, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testShouldCreateDelayedJobAddDependentJobAndReturnACKOnEmptyExportResult()
    {
        $messageBody = ['jobName' => 'job_name', 'exportType' => 'export_type', 'outputFormat' => 'output_format'];
        $jobUniqueName = 'job_unique_name';
        $message = new NullMessage();
        $message->setMessageId(123);

        $job = $this->createJob(1);
        $childJob = $this->createJob(10, $job);

        $jobRunner = $this->createJobRunnerMock();
        $jobRunner
            ->expects($this->once())
            ->method('runUnique')
            ->with($this->equalTo($message->getMessageId()), $this->equalTo($jobUniqueName))
            ->will($this->returnCallback(function ($jobId, $name, $callback) use ($jobRunner, $childJob) {
                return $callback($jobRunner, $childJob);
            }))
        ;

        $jobRunner
            ->expects($this->once())
            ->method('createDelayed')
            ->with($jobUniqueName.'.chunk.1')
        ;

        $user = $this->createUserStub();
        $user
            ->expects($this->once())
            ->method('getId')
            ->willReturn(self::USER_ID)
        ;
        $user
            ->expects($this->once())
            ->method('getEmail')
        ;

        $token = $this->createTokenMock();
        $token
            ->expects($this->any())
            ->method('getUser')
            ->willReturn($user)
        ;

        $tokenStorage = $this->createTokenStorageMock();
        $tokenStorage
            ->expects($this->any())
            ->method('getToken')
            ->willReturn($token)
        ;

        $dependentJobContext = $this->createDependentJobContextMock();
        $dependentJobContext
            ->expects($this->once())
            ->method('addDependentJob')
            ->with(
                Topics::POST_EXPORT,
                $this->callback(function ($message) {
                    return !empty($message['recipientUserId']) && $message['recipientUserId'] === self::USER_ID;
                })
            )
        ;

        $dependentJob = $this->createDependentJobMock();
        $dependentJob
            ->expects($this->once())
            ->method('createDependentJobContext')
            ->with($this->equalTo($job))
            ->willReturn($dependentJobContext)
        ;
        $dependentJob
            ->expects($this->once())
            ->method('saveDependentJob')
            ->with($this->equalTo($dependentJobContext))
        ;

        $processor = $this->getMockBuilder(PreExportMessageProcessorAbstract::class)
            ->setConstructorArgs([
                $jobRunner,
                $this->createMessageProducerMock(),
                $tokenStorage,
                $dependentJob,
                $this->createLoggerMock(),
                100
            ])
            ->setMethods([
                'getJobUniqueName',
                'getExportingEntityIds',
                'getDelayedJobCallback',
                'getMessageBody',
                'getSubscribedTopics'
            ])
            ->getMock()
        ;
        $processor
            ->expects($this->once())
            ->method('getMessageBody')
            ->willReturn($messageBody)
        ;
        $processor
            ->expects($this->once())
            ->method('getJobUniqueName')
            ->with($this->equalTo($messageBody))
            ->willReturn($jobUniqueName)
        ;
        $processor
            ->expects($this->once())
            ->method('getExportingEntityIds')
            ->with($this->equalTo($messageBody))
            ->willReturn([])
        ;
        $processor
            ->expects($this->once())
            ->method('getDelayedJobCallback')
            ->with($this->equalTo($messageBody))
            ->willReturn($this->getAnonymousFunction())
        ;

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(PreExportMessageProcessorAbstract::ACK, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testShouldCreateTwoDelayedJobsAddDependentJobAndReturnACKOnTwoExportResultChunks()
    {
        $messageBody = ['jobName' => 'job_name', 'exportType' => 'export_type', 'outputFormat' => 'output_format'];
        $jobUniqueName = 'job_unique_name';
        $message = new NullMessage();
        $message->setMessageId(123);

        $job = $this->createJob(1);
        $childJob = $this->createJob(10, $job);

        $jobRunner = $this->createJobRunnerMock();
        $jobRunner
            ->expects($this->once())
            ->method('runUnique')
            ->with($this->equalTo($message->getMessageId()), $this->equalTo($jobUniqueName))
            ->will($this->returnCallback(function ($jobId, $name, $callback) use ($jobRunner, $childJob) {
                return $callback($jobRunner, $childJob);
            }))
        ;
        $jobRunner
            ->expects($this->at(0))
            ->method('createDelayed')
            ->with($jobUniqueName.'.chunk.1')
        ;
        $jobRunner
            ->expects($this->at(1))
            ->method('createDelayed')
            ->with($jobUniqueName.'.chunk.2')
        ;

        $user = $this->createUserStub();
        $user
            ->expects($this->once())
            ->method('getId')
            ->willReturn(self::USER_ID)
        ;

        $user
            ->expects($this->once())
            ->method('getEmail')
        ;

        $token = $this->createTokenMock();
        $token
            ->expects($this->any())
            ->method('getUser')
            ->willReturn($user)
        ;

        $tokenStorage = $this->createTokenStorageMock();
        $tokenStorage
            ->expects($this->any())
            ->method('getToken')
            ->willReturn($token)
        ;

        $dependentJobContext = $this->createDependentJobContextMock();
        $dependentJobContext
            ->expects($this->once())
            ->method('addDependentJob')
            ->with(
                Topics::POST_EXPORT,
                $this->callback(function ($message) {
                    return !empty($message['recipientUserId']) && $message['recipientUserId'] === self::USER_ID;
                })
            )
        ;

        $dependentJob = $this->createDependentJobMock();
        $dependentJob
            ->expects($this->once())
            ->method('createDependentJobContext')
            ->with($this->equalTo($job))
            ->willReturn($dependentJobContext)
        ;
        $dependentJob
            ->expects($this->once())
            ->method('saveDependentJob')
            ->with($this->equalTo($dependentJobContext))
        ;

        $processor = $this->getMockBuilder(PreExportMessageProcessorAbstract::class)
            ->setConstructorArgs([
                $jobRunner,
                $this->createMessageProducerMock(),
                $tokenStorage,
                $dependentJob,
                $this->createLoggerMock(),
                1
            ])
            ->setMethods([
                'getJobUniqueName',
                'getExportingEntityIds',
                'getDelayedJobCallback',
                'getMessageBody',
                'getSubscribedTopics'
            ])
            ->getMock()
        ;
        $processor
            ->expects($this->once())
            ->method('getMessageBody')
            ->willReturn($messageBody)
        ;
        $processor
            ->expects($this->once())
            ->method('getJobUniqueName')
            ->with($this->equalTo($messageBody))
            ->willReturn($jobUniqueName)
        ;
        $processor
            ->expects($this->once())
            ->method('getExportingEntityIds')
            ->with($this->equalTo($messageBody))
            ->willReturn([1, 2])
        ;
        $processor
            ->expects($this->exactly(2))
            ->method('getDelayedJobCallback')
            ->with($this->equalTo($messageBody))
            ->willReturn($this->getAnonymousFunction())
        ;

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(PreExportMessageProcessorAbstract::ACK, $result);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->createMock(SessionInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|JobRunner
     */
    private function createJobRunnerMock()
    {
        return $this->createMock(JobRunner::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|MessageProducerInterface
     */
    private function createMessageProducerMock()
    {
        return $this->createMock(MessageProducerInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|TokenStorageInterface
     */
    private function createTokenStorageMock()
    {
        return $this->createMock(TokenStorageInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|DependentJobService
     */
    private function createDependentJobMock()
    {
        return $this->createMock(DependentJobService::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|DependentJobContext
     */
    private function createDependentJobContextMock()
    {
        return $this->createMock(DependentJobContext::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|TokenInterface
     */
    private function createTokenMock()
    {
        return $this->createMock(TokenInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|UserInterface
     */
    private function createUserMock()
    {
        return $this->createMock(UserInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|UserInterface
     */
    private function createUserStub()
    {
        return $this->createPartialMock(
            UserInterface::class,
            ['getId', 'getEmail', 'getRoles', 'getPassword', 'getSalt', 'getUsername', 'eraseCredentials']
        );
    }

    /**
     * @param int $id
     * @param Job $rootJob
     *
     * @return Job
     */
    private function createJob($id, $rootJob = null)
    {
        $job = new Job();
        $job->setId($id);
        if ($rootJob instanceof Job) {
            $job->setRootJob($rootJob);
        }

        return $job;
    }

    /**
     * @return \Closure
     */
    private function getAnonymousFunction()
    {
        return function () {
        };
    }
}
