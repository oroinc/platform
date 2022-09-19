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
    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var ReminderRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var ReminderSender|\PHPUnit\Framework\MockObject\MockObject */
    private $sender;

    /** @var SendRemindersCommand */
    private $command;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->repository = $this->createMock(ReminderRepository::class);
        $this->sender = $this->createMock(ReminderSender::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($this->entityManager);

        $doctrine->expects($this->any())
            ->method('getRepository')
            ->with(Reminder::class)
            ->willReturn($this->repository);

        $this->command = new SendRemindersCommand($doctrine, $this->sender);
    }

    public function testGetDefaultDefinition()
    {
        $this->assertEquals('*/1 * * * *', $this->command->getDefaultDefinition());
    }

    public function testExecute()
    {
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $reminders = [
            $this->createMock(Reminder::class),
            $this->createMock(Reminder::class),
            $this->createMock(Reminder::class)
        ];

        $this->repository->expects($this->once())
            ->method('findRemindersToSend')
            ->willReturn($reminders);

        $calls = [];

        $this->sender->expects($this->exactly(3))
            ->method('push')
            ->withConsecutive(
                [$this->identicalTo($reminders[0])],
                [$this->identicalTo($reminders[1])],
                [$this->identicalTo($reminders[2])]
            )
            ->willReturnCallback(function () use (&$calls) {
                $calls[] = 'push';
            });

        $this->sender->expects($this->once())
            ->method('send')
            ->willReturnCallback(function () use (&$calls) {
                $calls[] = 'send';
            });

        $reminders[0]->expects($this->exactly(2))
            ->method('getState')
            ->willReturn(Reminder::STATE_SENT);

        $reminders[1]->expects($this->exactly(2))
            ->method('getState')
            ->willReturn(Reminder::STATE_NOT_SENT);

        $reminders[2]->expects($this->exactly(2))
            ->method('getState')
            ->willReturn(Reminder::STATE_FAIL);

        $failId = 100;
        $reminders[2]->expects($this->once())
            ->method('getId')
            ->willReturn($failId);

        $failException = ['class' => 'ExceptionClass', 'message' => 'Exception message'];
        $reminders[2]->expects($this->once())
            ->method('getFailureException')
            ->willReturn($failException);

        $output->expects($this->exactly(4))
            ->method('writeln')
            ->withConsecutive(
                ['<comment>Reminders to send:</comment> 3'],
                ["<error>Failed to send reminder with id=$failId</error>"],
                ["<info>{$failException['class']}</info>: {$failException['message']}"],
                ["<info>Reminders sent:</info> 1"]
            );

        $this->entityManager->expects($this->once())
            ->method('beginTransaction')
            ->willReturnCallback(function () use (&$calls) {
                $calls[] = 'beginTransaction';
            });
        $this->entityManager->expects($this->once())
            ->method('flush')
            ->willReturnCallback(function () use (&$calls) {
                $calls[] = 'flushData';
            });
        $this->entityManager->expects($this->once())
            ->method('commit')
            ->willReturnCallback(function () use (&$calls) {
                $calls[] = 'commitTransaction';
            });

        $this->command->execute($input, $output);

        self::assertEquals(
            [
                'beginTransaction',
                'push',
                'push',
                'push',
                'send',
                'flushData',
                'commitTransaction'
            ],
            $calls
        );
    }

    public function testExecuteNoRemindersToSend()
    {
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $this->repository->expects($this->once())
            ->method('findRemindersToSend')
            ->willReturn([]);

        $output->expects($this->once())
            ->method('writeln')
            ->with('<info>No reminders to sent</info>');

        $this->command->execute($input, $output);
    }

    public function testExecuteRollbackTransaction()
    {
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $reminder = $this->createMock(Reminder::class);

        $this->repository->expects($this->once())
            ->method('findRemindersToSend')
            ->willReturn([$reminder]);

        $output->expects($this->once())
            ->method('writeln')
            ->with('<comment>Reminders to send:</comment> 1');

        $calls = [];

        $this->sender->expects($this->once())
            ->method('push')
            ->with($reminder)
            ->willReturnCallback(function () use (&$calls) {
                $calls[] = 'push';
            });
        $this->sender->expects($this->once())
            ->method('send')
            ->willThrowException(new \Exception('Test Exception'));

        $this->entityManager->expects($this->once())
            ->method('beginTransaction')
            ->willReturnCallback(function () use (&$calls) {
                $calls[] = 'beginTransaction';
            });
        $this->entityManager->expects($this->once())
            ->method('rollback')
            ->willReturnCallback(function () use (&$calls) {
                $calls[] = 'rollbackTransaction';
            });

        try {
            $this->command->execute($input, $output);
            $this->fail('Expected exception');
        } catch (\Exception $e) {
            $this->assertEquals('Test Exception', $e->getMessage());
        }

        self::assertEquals(
            [
                'beginTransaction',
                'push',
                'rollbackTransaction'
            ],
            $calls
        );
    }
}
