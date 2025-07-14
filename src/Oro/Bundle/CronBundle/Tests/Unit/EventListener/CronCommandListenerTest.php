<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\EventListener;

use Oro\Bundle\CronBundle\Command\CronCommandFeatureCheckerInterface;
use Oro\Bundle\CronBundle\EventListener\CronCommandListener;
use Oro\Bundle\CronBundle\Tests\Unit\Stub\ActivableCronCommandStub;
use Oro\Bundle\CronBundle\Tests\Unit\Stub\CronCommandStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;

class CronCommandListenerTest extends TestCase
{
    private CronCommandFeatureCheckerInterface&MockObject $commandFeatureChecker;
    private CronCommandListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->commandFeatureChecker = $this->createMock(CronCommandFeatureCheckerInterface::class);

        $this->listener = new CronCommandListener($this->commandFeatureChecker);
    }

    public function testForNotCronCommand(): void
    {
        $this->commandFeatureChecker->expects(self::never())
            ->method('isFeatureEnabled');

        $command = new Command('oro:test');
        $output = new BufferedOutput();
        $event = new ConsoleCommandEvent($command, $this->createMock(InputInterface::class), $output);
        $this->listener->onConsoleCommand($event);

        $this->assertTrue($event->commandShouldRun());
        $this->assertEquals('', $output->fetch());
    }

    public function testWhenCommandFeatureEnabled(): void
    {
        $this->commandFeatureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('oro:cron:test')
            ->willReturn(true);

        $command = new CronCommandStub('oro:cron:test');
        $output = new BufferedOutput();
        $event = new ConsoleCommandEvent($command, $this->createMock(InputInterface::class), $output);
        $this->listener->onConsoleCommand($event);

        $this->assertTrue($event->commandShouldRun());
        $this->assertEquals('', $output->fetch());
    }

    public function testWhenCommandFeatureDisabled(): void
    {
        $this->commandFeatureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('oro:cron:test')
            ->willReturn(false);

        $command = new CronCommandStub('oro:cron:test');
        $output = new BufferedOutput();
        $event = new ConsoleCommandEvent($command, $this->createMock(InputInterface::class), $output);
        $this->listener->onConsoleCommand($event);

        $this->assertFalse($event->commandShouldRun());
        $this->assertEquals(
            'The feature that enables this CRON command is turned off.',
            str_replace(PHP_EOL, '', $output->fetch())
        );
    }

    public function testWhenCommandFeatureEnabledAndCommandActive(): void
    {
        $this->commandFeatureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('oro:cron:test')
            ->willReturn(true);

        $command = new ActivableCronCommandStub('oro:cron:test');
        $output = new BufferedOutput();
        $event = new ConsoleCommandEvent($command, $this->createMock(InputInterface::class), $output);
        $this->listener->onConsoleCommand($event);

        $this->assertTrue($event->commandShouldRun());
        $this->assertEquals('', $output->fetch());
    }

    public function testWhenCommandFeatureEnabledAndCommandNotActive(): void
    {
        $this->commandFeatureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('oro:cron:test')
            ->willReturn(true);

        $command = new ActivableCronCommandStub('oro:cron:test');
        $command->setActive(false);
        $output = new BufferedOutput();
        $event = new ConsoleCommandEvent($command, $this->createMock(InputInterface::class), $output);
        $this->listener->onConsoleCommand($event);

        $this->assertFalse($event->commandShouldRun());
        $this->assertStringContainsString('This CRON command is disabled.', $output->fetch());
    }
}
