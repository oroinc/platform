<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\ReminderBundle\Command\SendRemindersCommand;

class SendRemindersCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $container;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrine;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $sender;

    /**
     * @var SendRemindersCommand
     */
    protected $command;

    protected function setUp()
    {
        $this->container = $this->getMock('Symfony\\Component\\DependencyInjection\\ContainerInterface');
        $this->doctrine = $this->getMock('Doctrine\\Common\\Persistence\\ManagerRegistry');
        $this->entityManager = $this->getMockBuilder('Doctrine\\ORM\\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->repository = $this->getMockBuilder('Oro\\Bundle\\ReminderBundle\\Entity\\Repository\\ReminderRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->sender = $this->getMockBuilder('Oro\\Bundle\\ReminderBundle\\Model\\ReminderSender')
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

    public function testExecute()
    {
        $input = $this->getMock('Symfony\\Component\\Console\\Input\\InputInterface');
        $output = $this->getMock('Symfony\\Component\\Console\\Output\\OutputInterface');

        $reminders = array($this->createReminder(), $this->createReminder());

        $this->repository->expects($this->once())
            ->method('findNotSentReminders')
            ->will($this->returnValue($reminders));

        $output->expects($this->at(0))->method('writeln')->with('<comment>Reminders to send:</comment> 2');

        $this->entityManager->expects($this->at(0))->method('beginTransaction');

        $this->sender->expects($this->at(0))->method('send')->with($reminders[0]);
        $reminders[0]->expects($this->once())->method('isSent')->will($this->returnValue(true));

        $this->sender->expects($this->at(1))->method('send')->with($reminders[1]);
        $reminders[1]->expects($this->once())->method('isSent')->will($this->returnValue(false));

        $output->expects($this->at(1))->method('writeln')->with('<info>Reminders sent:</info> 1');

        $this->entityManager->expects($this->at(1))->method('flush');
        $this->entityManager->expects($this->at(2))->method('commit');

        $this->command->execute($input, $output);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Test Exception
     */
    public function testExecuteRollbackTransation()
    {
        $input = $this->getMock('Symfony\\Component\\Console\\Input\\InputInterface');
        $output = $this->getMock('Symfony\\Component\\Console\\Output\\OutputInterface');

        $reminder = $this->createReminder();

        $this->repository->expects($this->once())
            ->method('findNotSentReminders')
            ->will($this->returnValue(array($reminder)));

        $output->expects($this->at(0))->method('writeln')->with('<comment>Reminders to send:</comment> 1');

        $this->entityManager->expects($this->at(0))->method('beginTransaction');

        $this->sender->expects($this->once())->method('send')
            ->with($reminder)
            ->will($this->throwException(new \Exception('Test Exception')));

        $this->entityManager->expects($this->at(1))->method('rollback');

        $this->command->execute($input, $output);
    }

    protected function createReminder()
    {
        return $this->getMock('Oro\\Bundle\\ReminderBundle\\Entity\\Reminder');
    }
}
