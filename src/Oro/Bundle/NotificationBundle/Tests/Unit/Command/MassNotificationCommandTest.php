<?php

namespace  Oro\Bundle\NotificationBundle\Tests\Unit\Command;

use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\NotificationBundle\Command\MassNotificationCommand;
use Oro\Bundle\NotificationBundle\Exception\NotificationSendException;
use Oro\Bundle\NotificationBundle\Model\MassNotificationSender;
use Oro\Bundle\NotificationBundle\Model\TemplateEmailNotification;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MassNotificationCommandTest extends TestCase
{
    private MassNotificationSender&MockObject $sender;
    private LoggerInterface&MockObject $logger;
    private InputInterface&MockObject $in;
    private OutputInterface&MockObject $out;
    private MassNotificationCommand $command;

    #[\Override]
    protected function setUp(): void
    {
        $this->sender = $this->createMock(MassNotificationSender::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->in = $this->createMock(InputInterface::class);
        $this->out = $this->createMock(OutputInterface::class);

        $this->command = new MassNotificationCommand($this->sender, $this->logger);
    }

    public function testConfigure(): void
    {
        $this->command->configure();
        $this->assertEquals('oro:maintenance-notification', $this->command->getName());
        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasOption('subject'));
        $this->assertTrue($definition->hasOption('message'));
        $this->assertTrue($definition->hasOption('file'));
        $this->assertTrue($definition->hasOption('sender_name'));
        $this->assertTrue($definition->hasOption('sender_email'));
    }

    public function testExecuteWithMessage(): void
    {
        $count = 2;
        $this->out->expects($this->once())
            ->method('writeln')
            ->with(sprintf('%s notifications have been added to the queue', $count));

        $this->in->expects($this->any())
            ->method('getOption')
            ->willReturnMap([
                ['message', 'test message'],
                ['subject', 'test subject'],
                ['sender_email', 'test@test.com'],
                ['sender_name', 'test name'],
                ['file', null]
            ]);

        $this->sender->expects($this->once())
            ->method('send')
            ->with(
                'test message',
                'test subject',
                From::emailAddress('test@test.com', 'test name')
            )
            ->willReturn($count);

        $this->command->execute($this->in, $this->out);
    }

    public function testExecuteWithMessageWhenCouldNotSendNotification(): void
    {
        $this->out->expects($this->once())
            ->method('writeln')
            ->with('An error occurred while sending mass notification');

        $this->in->expects($this->any())
            ->method('getOption')
            ->willReturnMap([
                ['message', 'test message'],
                ['subject', 'test subject'],
                ['sender_email', 'test@test.com'],
                ['sender_name', 'test name'],
                ['file', null]
            ]);

        $notification = new TemplateEmailNotification(new EmailTemplateCriteria('template'), []);
        $exception = new NotificationSendException($notification);
        $this->sender->expects($this->once())
            ->method('send')
            ->willThrowException($exception);
        $this->logger->expects($this->once())
            ->method('error')
            ->with('An error occurred while sending mass notification');

        $this->command->execute($this->in, $this->out);
    }

    public function testExecuteWithFile(): void
    {
        $count = 2;

        $this->out->expects($this->once())
            ->method('writeln')
            ->with(sprintf('%s notifications have been added to the queue', $count));
        $this->in->expects($this->any())
            ->method('getOption')
            ->willReturnMap([
                ['message', null],
                ['subject', null],
                ['sender_email', null],
                ['sender_name', null],
                ['file', __DIR__ . '/File/message.txt']
            ]);

        $this->sender->expects($this->once())
            ->method('send')
            ->with('file test message', null, null)
            ->willReturn($count);

        $this->command->execute($this->in, $this->out);
    }

    public function testExecuteNoFileFound(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Could not read notfoundpath file');

        $this->in->expects($this->any())
            ->method('getOption')
            ->willReturnMap([
                ['file', 'notfoundpath']
            ]);

        $this->command->execute($this->in, $this->out);
    }
}
