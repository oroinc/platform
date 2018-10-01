<?php

namespace  Oro\Bundle\NotificationBundle\Tests\Unit\Command;

use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\NotificationBundle\Command\MassNotificationCommand;
use Oro\Bundle\NotificationBundle\Exception\NotificationSendException;
use Oro\Bundle\NotificationBundle\Model\MassNotificationSender;
use Oro\Bundle\NotificationBundle\Model\TemplateEmailNotification;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MassNotificationCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $container;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $sender;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface
     */
    protected $logger;

    /**
     * @var MassNotificationCommand
     */
    protected $command;

    /**
     * @var InputInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $in;

    /**
     * @var OutputInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $out;

    protected function setUp()
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->sender = $this->createMock(MassNotificationSender::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->in  = $this->createMock(InputInterface::class);
        $this->out = $this->createMock(OutputInterface::class);

        $this->command = new MassNotificationCommand();
        $this->command->setContainer($this->container);
    }

    public function testConfigure()
    {
        $this->command->configure();
        $this->assertEquals($this->command->getName(), MassNotificationCommand::COMMAND_NAME);
        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasOption('subject'));
        $this->assertTrue($definition->hasOption('message'));
        $this->assertTrue($definition->hasOption('file'));
        $this->assertTrue($definition->hasOption('sender_name'));
        $this->assertTrue($definition->hasOption('sender_email'));
    }

    public function testExecuteWithMessage()
    {
        $count = 2;
        $this->out->expects($this->at(0))->method('writeln')->with(
            sprintf('%s notifications have been added to the queue', $count)
        );

        $this->in->expects($this->any())->method('getOption')->will(
            $this->returnValueMap(
                [
                    ['message', 'test message'],
                    ['subject', 'test subject'],
                    ['sender_email', 'test@test.com'],
                    ['sender_name', 'test name'],
                    ['file', null]
                ]
            )
        );

        $this->sender->expects($this->once())->method('send')->with(
            'test message',
            'test subject',
            From::emailAddress('test@test.com', 'test name')
        )->will($this->returnValue($count));

        $this->configureContainer();

        $this->command->execute($this->in, $this->out);
    }

    public function testExecuteWithMessageWhenCouldNotSendNotification()
    {
        $this->out->expects($this->at(0))->method('writeln')->with('An error occurred while sending mass notification');

        $this->in->expects($this->any())->method('getOption')->will(
            $this->returnValueMap(
                [
                    ['message', 'test message'],
                    ['subject', 'test subject'],
                    ['sender_email', 'test@test.com'],
                    ['sender_name', 'test name'],
                    ['file', null]
                ]
            )
        );

        $notification = new TemplateEmailNotification(new EmailTemplateCriteria('template'), []);
        $exception = new NotificationSendException($notification);
        $this->sender->expects($this->once())->method('send')->willThrowException($exception);
        $this->logger->expects($this->once())->method('error')
            ->with('An error occurred while sending mass notification');

        $this->configureContainer();

        $this->command->execute($this->in, $this->out);
    }

    public function testExecuteWithFile()
    {
        $count = 2;

        $this->out->expects($this->at(0))->method('writeln')->with(
            sprintf('%s notifications have been added to the queue', $count)
        );
        $this->in->expects($this->any())->method('getOption')->will(
            $this->returnValueMap(
                [
                    ['message', null],
                    ['subject', null],
                    ['sender_email', null],
                    ['sender_name', null],
                    ['file', __DIR__.'/File/message.txt']
                ]
            )
        );

        $this->sender->expects($this->once())->method('send')->with(
            'file test message',
            null,
            null
        )->will($this->returnValue($count));

        $this->configureContainer();

        $this->command->execute($this->in, $this->out);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Could not read notfoundpath file
     */
    public function testExecuteNoFileFound()
    {
        $this->in->expects($this->any())->method('getOption')->will(
            $this->returnValueMap(
                [
                    ['file', 'notfoundpath']
                ]
            )
        );

        $this->command->execute($this->in, $this->out);
    }

    private function configureContainer(): void
    {
        $this->container->expects($this->any())
            ->method('get')
            ->willReturnMap([
                [
                    'oro_notification.mass_notification_sender',
                    ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                    $this->sender
                ],
                [
                    'logger',
                    ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                    $this->logger
                ],
            ]);
    }
}
