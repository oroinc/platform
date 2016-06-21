<?php

namespace  Oro\Bundle\NotificationBundle\Tests\Unit\Command;

use Oro\Bundle\NotificationBundle\Command\MassNotificationCommand;

class MassNotificationCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $container;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $sender;

    /**
     * @var MassNotificationCommand
     */
    protected $command;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $in;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $out;

    protected function setUp()
    {
        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');

        $this->sender = $this->getMockBuilder('Oro\Bundle\NotificationBundle\Model\MassNotificationSender')
            ->disableOriginalConstructor()
            ->getMock();

        $this->container->expects($this->any())->method('get')->with('oro_notification.mass_notification_sender')
            ->will($this->returnValue($this->sender));

        $this->in  = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $this->out = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $this->command = new MassNotificationCommand();
        $this->command->setContainer($this->container);
    }
    
    protected function tearDown()
    {
        unset($this->command);
        unset($this->sender);
        unset($this->container);
        unset($this->in);
        unset($this->out);
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
            'test@test.com',
            'test name'
        )->will($this->returnValue($count));

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
            null,
            null
        )->will($this->returnValue($count));

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
}
