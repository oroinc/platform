<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\EventListener;

use Oro\Bundle\CronBundle\EventListener\CronCommandListener;
use Oro\Bundle\CronBundle\Tests\Unit\Stub\CronCommandStub;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;

class CronCommandListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var CronCommandListener */
    private $listener;

    protected function setUp(): void
    {
        $this->listener = new CronCommandListener();
    }

    public function testForNotCronCommand(): void
    {
        $command = new Command('oro:test');
        $output = new BufferedOutput();
        $event = new ConsoleCommandEvent($command, $this->createMock(InputInterface::class), $output);
        $this->listener->onConsoleCommand($event);

        $this->assertTrue($event->commandShouldRun());
        $this->assertEquals('', $output->fetch());
    }

    public function testWhenCommandActive(): void
    {
        $command = new CronCommandStub('oro:cron:test');
        $output = new BufferedOutput();
        $event = new ConsoleCommandEvent($command, $this->createMock(InputInterface::class), $output);
        $this->listener->onConsoleCommand($event);

        $this->assertTrue($event->commandShouldRun());
        $this->assertEquals('', $output->fetch());
    }

    public function testWhenCommandNotActive(): void
    {
        $command = new CronCommandStub('oro:cron:test');
        $command->setActive(false);
        $output = new BufferedOutput();
        $event = new ConsoleCommandEvent($command, $this->createMock(InputInterface::class), $output);
        $this->listener->onConsoleCommand($event);

        $this->assertFalse($event->commandShouldRun());
        $this->assertStringContainsString('This CRON command is disabled.', $output->fetch());
    }
}
