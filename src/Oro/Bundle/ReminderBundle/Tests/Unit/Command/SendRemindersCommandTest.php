<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ReminderBundle\Command\SendRemindersCommand;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Entity\Repository\ReminderRepository;
use Oro\Bundle\ReminderBundle\Model\ReminderSender;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendRemindersCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EntityManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $entityManager;

    /**
     * @var ReminderRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $repository;

    /**
     * @var ReminderSender|\PHPUnit\Framework\MockObject\MockObject
     */
    private $sender;

    /**
     * @var SendRemindersCommand
     */
    private $command;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->repository = $this->createMock(ReminderRepository::class);
        $this->sender = $this->createMock(ReminderSender::class);

        /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $doctrine */
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())->method('getManager')
            ->willReturn($this->entityManager);

        $doctrine->expects($this->any())->method('getRepository')
            ->with('OroReminderBundle:Reminder')
            ->willReturn($this->repository);

        $this->command = new SendRemindersCommand($doctrine, $this->sender);
    }

    public function testGetDefaultDefinition()
    {
        $this->assertEquals('*/1 * * * *', $this->command->getDefaultDefinition());
    }

    public function testExecute()
    {
        $input  = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $reminders = [$this->createReminder(), $this->createReminder(), $this->createReminder()];

        $this->repository->expects($this->once())
            ->method('findRemindersToSend')
            ->willReturn($reminders);

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
            ->willReturn(Reminder::STATE_SENT);

        $reminders[1]
            ->expects($this->exactly(2))
            ->method('getState')
            ->willReturn(Reminder::STATE_NOT_SENT);

        $reminders[2]
            ->expects($this->exactly(2))
            ->method('getState')
            ->willReturn(Reminder::STATE_FAIL);

        $failId = 100;
        $reminders[2]
            ->expects($this->once())
            ->method('getId')
            ->willReturn($failId);

        $failException = array('class' => 'ExceptionClass', 'message' => 'Exception message');
        $reminders[2]
            ->expects($this->once())
            ->method('getFailureException')
            ->willReturn($failException);

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
        $input  = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $this->repository->expects($this->once())
            ->method('findRemindersToSend')
            ->willReturn([]);

        $output->expects($this->at(0))->method('writeln')->with('<info>No reminders to sent</info>');

        $this->command->execute($input, $output);
    }

    public function testExecuteRollbackTransation()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Test Exception');

        $input  = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $reminder = $this->createReminder();

        $this->repository->expects($this->once())
            ->method('findRemindersToSend')
            ->willReturn([$reminder]);

        $output->expects($this->at(0))->method('writeln')->with('<comment>Reminders to send:</comment> 1');

        $this->entityManager->expects($this->at(0))->method('beginTransaction');

        $this->sender->expects($this->at(0))->method('push')
            ->with($reminder);

        $this->sender->expects($this->at(1))->method('send')
            ->willThrowException(new \Exception('Test Exception'));

        $this->entityManager->expects($this->at(1))->method('rollback');

        $this->command->execute($input, $output);
    }

    protected function createReminder()
    {
        return $this->createMock(Reminder::class);
    }
}
