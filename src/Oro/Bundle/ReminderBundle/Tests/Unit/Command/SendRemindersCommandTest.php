<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit;

use Oro\Bundle\ReminderBundle\Command\SendRemindersCommand;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SendRemindersCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $container;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrine;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $entityManager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $repository;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $sender;

    /**
     * @var SendRemindersCommand
     */
    protected $command;

    protected function setUp()
    {
        $this->container     = $this->createMock('Symfony\\Component\\DependencyInjection\\ContainerInterface');
        $this->doctrine      = $this->createMock('Doctrine\\Common\\Persistence\\ManagerRegistry');
        $this->entityManager = $this->getMockBuilder('Doctrine\\ORM\\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->repository    = $this->getMockBuilder(
            'Oro\\Bundle\\ReminderBundle\\Entity\\Repository\\ReminderRepository'
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->sender        = $this->getMockBuilder('Oro\\Bundle\\ReminderBundle\\Model\\ReminderSender')
            ->disableOriginalConstructor()
            ->getMock();

        $this->container->expects($this->any())->method('get')
            ->will(
                $this->returnValueMap(
                    array(
                        array('doctrine', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->doctrine),
                        array('oro_reminder.sender', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->sender),
                    )
                )
            );

        $this->doctrine->expects($this->any())->method('getManager')
            ->will($this->returnValue($this->entityManager));

        $this->doctrine->expects($this->any())->method('getRepository')
            ->with('OroReminderBundle:Reminder')
            ->will($this->returnValue($this->repository));

        $this->command = new SendRemindersCommand();
        $this->command->setContainer($this->container);
    }

    public function testGetDefaultDefinition()
    {
        $this->assertEquals('*/1 * * * *', $this->command->getDefaultDefinition());
    }

    public function testExecute()
    {
        $input  = $this->createMock('Symfony\\Component\\Console\\Input\\InputInterface');
        $output = $this->createMock('Symfony\\Component\\Console\\Output\\OutputInterface');

        $reminders = array($this->createReminder(), $this->createReminder(), $this->createReminder());

        $this->repository->expects($this->once())
            ->method('findRemindersToSend')
            ->will($this->returnValue($reminders));

        $output->expects($this->at(0))->method('writeln')->with('<comment>Reminders to send:</comment> 3');

        $this->entityManager->expects($this->at(0))->method('beginTransaction');

        $this->sender
            ->expects($this->at(0))
            ->method('push')
            ->with($reminders[0]);

        $this->sender
            ->expects($this->at(1))
            ->method('push')
            ->with($reminders[1]);

        $this->sender
            ->expects($this->at(2))
            ->method('push')
            ->with($reminders[1]);

        $this->sender
            ->expects($this->at(3))
            ->method('send')
            ->with();

        $reminders[0]
            ->expects($this->exactly(2))
            ->method('getState')
            ->will($this->returnValue(Reminder::STATE_SENT));

        $reminders[1]
            ->expects($this->exactly(2))
            ->method('getState')
            ->will($this->returnValue(Reminder::STATE_NOT_SENT));

        $reminders[2]
            ->expects($this->exactly(2))
            ->method('getState')
            ->will($this->returnValue(Reminder::STATE_FAIL));

        $failId = 100;
        $reminders[2]
            ->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($failId));

        $failException = array('class' => 'ExceptionClass', 'message' => 'Exception message');
        $reminders[2]
            ->expects($this->once())
            ->method('getFailureException')
            ->will($this->returnValue($failException));

        $output->expects($this->at(1))
            ->method('writeln')
            ->with("<error>Failed to send reminder with id=$failId</error>");

        $output->expects($this->at(2))
            ->method('writeln')
            ->with("<info>{$failException['class']}</info>: {$failException['message']}");

        $output->expects($this->at(3))->method('writeln')->with("<info>Reminders sent:</info> 1");

        $this->entityManager->expects($this->at(1))->method('flush');
        $this->entityManager->expects($this->at(2))->method('commit');

        $this->command->execute($input, $output);
    }

    public function testExecuteNoRemindersToSend()
    {
        $input  = $this->createMock('Symfony\\Component\\Console\\Input\\InputInterface');
        $output = $this->createMock('Symfony\\Component\\Console\\Output\\OutputInterface');

        $this->repository->expects($this->once())
            ->method('findRemindersToSend')
            ->will($this->returnValue(array()));

        $output->expects($this->at(0))->method('writeln')->with('<info>No reminders to sent</info>');

        $this->command->execute($input, $output);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Test Exception
     */
    public function testExecuteRollbackTransation()
    {
        $input  = $this->createMock('Symfony\\Component\\Console\\Input\\InputInterface');
        $output = $this->createMock('Symfony\\Component\\Console\\Output\\OutputInterface');

        $reminder = $this->createReminder();

        $this->repository->expects($this->once())
            ->method('findRemindersToSend')
            ->will($this->returnValue(array($reminder)));

        $output->expects($this->at(0))->method('writeln')->with('<comment>Reminders to send:</comment> 1');

        $this->entityManager->expects($this->at(0))->method('beginTransaction');

        $this->sender->expects($this->at(0))->method('push')
            ->with($reminder);

        $this->sender->expects($this->at(1))->method('send')
            ->will($this->throwException(new \Exception('Test Exception')));

        $this->entityManager->expects($this->at(1))->method('rollback');

        $this->command->execute($input, $output);
    }

    protected function createReminder()
    {
        return $this->createMock('Oro\\Bundle\\ReminderBundle\\Entity\\Reminder');
    }
}
