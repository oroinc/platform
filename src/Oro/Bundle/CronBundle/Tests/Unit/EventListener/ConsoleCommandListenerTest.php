<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\EventListener;

use Oro\Bundle\CronBundle\EventListener\ConsoleCommandListener;
use Oro\Bundle\CronBundle\Tests\Unit\Stub\CronCommandStub;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;

class ConsoleCommandListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var ConsoleCommandListener */
    private $listener;

    protected function setUp(): void
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->listener = new ConsoleCommandListener($this->featureChecker);
    }

    public function testForCronCommand(): void
    {
        $this->featureChecker->expects(self::never())
            ->method('isResourceEnabled');

        $command = new CronCommandStub('oro:cron:test');
        $output = new BufferedOutput();
        $event = new ConsoleCommandEvent($command, $this->createMock(InputInterface::class), $output);
        $this->listener->onConsoleCommand($event);

        $this->assertTrue($event->commandShouldRun());
        $this->assertEquals('', $output->fetch());
    }

    public function testWhenCommandFeatureEnabledForNotCronCommand(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with('oro:search:index', 'commands')
            ->willReturn(true);

        $command = new Command('oro:search:index');
        $output = new BufferedOutput();
        $event = new ConsoleCommandEvent($command, $this->createMock(InputInterface::class), $output);
        $this->listener->onConsoleCommand($event);

        $this->assertTrue($event->commandShouldRun());
        $this->assertEquals('', $output->fetch());
    }

    public function testWhenCommandFeatureDisabledForNotCronCommand(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with('oro:search:index', 'commands')
            ->willReturn(false);

        $command = new Command('oro:search:index');
        $output = new BufferedOutput();
        $event = new ConsoleCommandEvent($command, $this->createMock(InputInterface::class), $output);
        $this->listener->onConsoleCommand($event);

        $this->assertFalse($event->commandShouldRun());
        $this->assertEquals(
            'The feature that enables this command is turned off.',
            str_replace(PHP_EOL, '', $output->fetch())
        );
    }
}
