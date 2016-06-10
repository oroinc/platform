<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Action;

use Doctrine\Common\Persistence\ObjectManager;

use JMS\JobQueueBundle\Entity\Job;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\CronBundle\Action\CreateJobAction;
use Oro\Bundle\CronBundle\Entity\Manager\JobManager;

use Oro\Component\Action\Model\ContextAccessor;

class CreateJobActionTest extends \PHPUnit_Framework_TestCase
{
    /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $eventDispatcher;

    /** @var JobManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $jobManager;

    /** @var \Doctrine\Common\Persistence\ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $managerRegistry;

    /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $objectManager;

    /** @var CreateJobAction */
    protected $createJobAction;

    protected function setUp()
    {
        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $this->jobManager = $this->getMockBuilder('Oro\Bundle\CronBundle\Entity\Manager\JobManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->objectManager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');

        $this->createJobAction = new CreateJobAction(new ContextAccessor(), $this->jobManager, $this->managerRegistry);
        $this->createJobAction->setDispatcher($this->eventDispatcher);
    }

    public function testInitialize()
    {
        $options = [
            CreateJobAction::OPTION_COMMAND => 'help',
            CreateJobAction::OPTION_ARGUMENTS => ['--env=dev'],
            CreateJobAction::OPTION_ALLOW_DUPLICATES => true,
            CreateJobAction::OPTION_PRIORITY => 100500,
            CreateJobAction::OPTION_QUEUE => 'special',
            CreateJobAction::OPTION_COMMIT => true,
            CreateJobAction::OPTION_ATTRIBUTE => new PropertyPath('property')
        ];

        $result = $this->createJobAction->initialize($options);

        $this->assertInstanceOf('Oro\Bundle\CronBundle\Action\CreateJobAction', $result);
        $this->assertAttributeEquals($options, 'options', $this->createJobAction);
    }

    public function testInitializeDefaults()
    {
        $options = [CreateJobAction::OPTION_COMMAND => 'help'];

        $result = $this->createJobAction->initialize($options);

        $this->assertInstanceOf('Oro\Bundle\CronBundle\Action\CreateJobAction', $result);
        $this->assertAttributeEquals(
            [
                'command' => 'help',
                'allow_duplicates' => false,
                'commit' => true,
                'arguments' => [],
                'queue' => 'default',
                'priority' => 0,
                'attribute' => null
            ],
            'options',
            $this->createJobAction
        );
    }

    /**
     * @dataProvider initializeExceptionsData
     *
     * @param array $options
     * @param string $exceptionMessage
     */
    public function testInitializeExceptions(array $options, $exceptionMessage)
    {
        $this->setExpectedException(
            'Oro\Component\Action\Exception\InvalidParameterException',
            $exceptionMessage
        );
        $this->createJobAction->initialize($options);
    }

    /**
     * @return array
     */
    public function initializeExceptionsData()
    {
        return [
            'case no command' => [
                [],
                'The required option "command" is missing.'
            ],
            'case invalid args' => [
                [
                    CreateJobAction::OPTION_COMMAND => 'help',
                    CreateJobAction::OPTION_ARGUMENTS => 'notarg'
                ],
                'The option "arguments" with value "notarg" is expected to be of type "array", but is of type "string".'
            ],
            'case attribute is wrong' => [
                [
                    CreateJobAction::OPTION_COMMAND => 'help',
                    CreateJobAction::OPTION_ATTRIBUTE => []
                ],
                'The option "attribute" with value array is expected to be of ' .
                'type "null" or "Symfony\Component\PropertyAccess\PropertyPathInterface", but is of type "array".'
            ],
            'case priority is not an int' => [
                [
                    CreateJobAction::OPTION_COMMAND => 'help',
                    CreateJobAction::OPTION_PRIORITY => []
                ],
                'The option "priority" with value array is expected to be of type "int", but is of type "array".'
            ],
            'case queue is not string' => [
                [
                    CreateJobAction::OPTION_COMMAND => 'help',
                    CreateJobAction::OPTION_QUEUE => []
                ],
                'The option "queue" with value array is expected to be of type "string", but is of type "array".'
            ]
        ];
    }

    public function testExecuteActionFull()
    {
        $this->createJobAction->initialize([
            CreateJobAction::OPTION_COMMAND => 'help',
            CreateJobAction::OPTION_ARGUMENTS => ['--env' => 'test'],
            CreateJobAction::OPTION_ALLOW_DUPLICATES => false,
            CreateJobAction::OPTION_COMMIT => true,
            CreateJobAction::OPTION_PRIORITY => 42,
            CreateJobAction::OPTION_QUEUE => 'testQueue',
            CreateJobAction::OPTION_ATTRIBUTE => new PropertyPath('result')
        ]);

        $this->jobManager->expects($this->once())
            ->method('hasJobInQueue')
            ->with('help', json_encode(['--env=test']))
            ->willReturn(false);

        $this->assertResolvingObjectManager('JMSJobQueueBundle:Job', $this->objectManager);

        $this->objectManager->expects($this->once())->method('persist');
        $this->objectManager->expects($this->once())->method('flush');

        $context = new ActionData();
        $this->createJobAction->execute($context);

        $this->assertArrayHasKey('result', $context, 'result must be provided');

        /** @var Job $job */
        $job = $context['result'];

        $this->assertInstanceOf('JMS\JobQueueBundle\Entity\Job', $job);
        $this->assertEquals('help', $job->getCommand());
        $this->assertEquals(['--env=test'], $job->getArgs());
        $this->assertEquals(Job::STATE_PENDING, $job->getState()); //confirmed by default
        $this->assertEquals('testQueue', $job->getQueue());
        $this->assertEquals(42, $job->getPriority());
    }

    public function testExecuteActionWithAllowedDuplications()
    {
        $this->createJobAction->initialize([
            CreateJobAction::OPTION_COMMAND => 'help',
            CreateJobAction::OPTION_ARGUMENTS => ['--env=test'],
            CreateJobAction::OPTION_ALLOW_DUPLICATES => true,
            CreateJobAction::OPTION_ATTRIBUTE => new PropertyPath('result')
        ]);

        $this->jobManager->expects($this->never())->method('hasJobInQueue'); //the case

        $this->assertResolvingObjectManager('JMSJobQueueBundle:Job', $this->objectManager);

        $this->objectManager->expects($this->once())->method('persist');
        $this->objectManager->expects($this->once())->method('flush');

        $context = new ActionData();
        $this->createJobAction->execute($context);

        /** @var Job $job */
        $job = $context['result'];
        $this->assertInstanceOf('JMS\JobQueueBundle\Entity\Job', $job);
    }

    public function testExecuteActionNotCreatedBecauseOfDuplicates()
    {
        $this->createJobAction->initialize([
            CreateJobAction::OPTION_COMMAND => 'help',
            CreateJobAction::OPTION_ALLOW_DUPLICATES => false,
            CreateJobAction::OPTION_ATTRIBUTE => new PropertyPath('result')
        ]);

        $this->jobManager->expects($this->once())
            ->method('hasJobInQueue')
            ->with('help', json_encode([]))
            ->willReturn(true); // <-- has stored one

        $this->objectManager->expects($this->never())->method('persist');
        $this->objectManager->expects($this->never())->method('flush');

        $context = new ActionData();
        $this->createJobAction->execute($context);

        $this->assertArrayNotHasKey('result', $context, 'no result should be provided');
    }

    public function testExecuteActionWithoutCommit()
    {
        $this->createJobAction->initialize([
            CreateJobAction::OPTION_COMMAND => 'help',
            CreateJobAction::OPTION_ARGUMENTS => ['--env' => 'test'],
            CreateJobAction::OPTION_ALLOW_DUPLICATES => false,
            CreateJobAction::OPTION_COMMIT => false, // <-- no commit
            CreateJobAction::OPTION_ATTRIBUTE => new PropertyPath('result')
        ]);

        $this->jobManager->expects($this->once())
            ->method('hasJobInQueue')
            ->with('help', json_encode(['--env=test']))
            ->willReturn(false);

        $this->assertResolvingObjectManager('JMSJobQueueBundle:Job', $this->objectManager);

        $this->objectManager->expects($this->once())->method('persist');
        $this->objectManager->expects($this->never())->method('flush'); // <-- not expected

        $context = new ActionData();
        $this->createJobAction->execute($context);

        $this->assertArrayHasKey('result', $context, 'result must be provided');

        /** @var Job $job */
        $job = $context['result'];
        $this->assertInstanceOf('JMS\JobQueueBundle\Entity\Job', $job);
    }

    public function testExecuteActionWithoutStoringToAttribute()
    {
        $this->createJobAction->initialize([
            CreateJobAction::OPTION_COMMAND => 'help',
            CreateJobAction::OPTION_ALLOW_DUPLICATES => false,
            CreateJobAction::OPTION_ATTRIBUTE => null //no property path (e.g - default value)
        ]);

        $this->jobManager->expects($this->once())
            ->method('hasJobInQueue')
            ->with('help', json_encode([]))
            ->willReturn(false);

        $this->assertResolvingObjectManager('JMSJobQueueBundle:Job', $this->objectManager);

        $this->objectManager->expects($this->once())->method('persist');
        $this->objectManager->expects($this->once())->method('flush');

        $context = new ActionData();
        $this->createJobAction->execute($context);

        $this->assertArrayNotHasKey('result', $context, 'no result should be provided');
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Action is not initialized.
     */
    public function testExecuteActionUninitialized()
    {
        $this->createJobAction->execute(new ActionData());
    }

    /**
     * @param string $string
     * @param ObjectManager $mockObject
     */
    private function assertResolvingObjectManager($string, $mockObject)
    {
        $this->managerRegistry->expects($this->once())
            ->method('getManagerForClass')
            ->with($string)
            ->willReturn($mockObject);
    }
}
