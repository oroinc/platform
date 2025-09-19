<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\EventListener;

use Oro\Bundle\ApiBundle\Collector\ApiDocWarningsCollector;
use Oro\Bundle\ApiBundle\EventListener\ApiDocCacheClearListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ApiDocCacheClearListenerTest extends TestCase
{
    private ApiDocWarningsCollector $collector;
    private ApiDocCacheClearListener $listener;

    protected function setUp(): void
    {
        $this->collector = $this->createMock(ApiDocWarningsCollector::class);
        $this->listener = new ApiDocCacheClearListener($this->collector);
    }

    public function testGetSubscribedEvents(): void
    {
        $expected = [
            ConsoleEvents::COMMAND => 'onConsoleCommand',
            ConsoleEvents::TERMINATE => 'onConsoleTerminate'
        ];

        self::assertEquals($expected, ApiDocCacheClearListener::getSubscribedEvents());
    }

    public function testOnConsoleCommandStartsCollectingForTargetCommand(): void
    {
        $command = new Command('oro:api:doc:cache:clear');
        $event = new ConsoleCommandEvent($command, new ArrayInput([]), new BufferedOutput());

        $this->collector->expects(self::once())
            ->method('startCollecting');

        $this->listener->onConsoleCommand($event);
    }

    public function testOnConsoleCommandIgnoresOtherCommands(): void
    {
        $command = new Command('cache:clear');
        $event = new ConsoleCommandEvent($command, new ArrayInput([]), new BufferedOutput());

        $this->collector->expects(self::never())
            ->method('startCollecting');

        $this->listener->onConsoleCommand($event);
    }

    public function testOnConsoleTerminateStopsCollectingForTargetCommand(): void
    {
        $command = new Command('oro:api:doc:cache:clear');
        $event = new ConsoleTerminateEvent($command, new ArrayInput([]), new BufferedOutput(), 0);

        $this->collector->expects(self::once())
            ->method('stopCollecting');
        $this->collector->expects(self::once())
            ->method('getWarnings')
            ->willReturn([]);

        $this->listener->onConsoleTerminate($event);
    }

    public function testOnConsoleTerminateIgnoresOtherCommands(): void
    {
        $command = new Command('cache:clear');
        $event = new ConsoleTerminateEvent($command, new ArrayInput([]), new BufferedOutput(), 0);

        $this->collector->expects(self::never())
            ->method('stopCollecting');

        $this->listener->onConsoleTerminate($event);
    }

    public function testOnConsoleTerminateDisplaysWarnings(): void
    {
        $warnings = ['Warning 1', 'Warning 2'];
        $command = new Command('oro:api:doc:cache:clear');
        $output = new BufferedOutput();
        $event = new ConsoleTerminateEvent($command, new ArrayInput([]), $output, 0);

        $this->collector->expects(self::once())
            ->method('stopCollecting');
        $this->collector->expects(self::once())
            ->method('getWarnings')
            ->willReturn($warnings);

        $this->listener->onConsoleTerminate($event);

        $outputContent = $output->fetch();
        self::assertStringContainsString('API Documentation warnings found:', $outputContent);
        self::assertStringContainsString('Total: 2 warning(s)', $outputContent);
    }

    public function testOnConsoleTerminateDoesNotDisplayWhenNoWarnings(): void
    {
        $command = new Command('oro:api:doc:cache:clear');
        $output = new BufferedOutput();
        $event = new ConsoleTerminateEvent($command, new ArrayInput([]), $output, 0);

        $this->collector->expects(self::once())
            ->method('stopCollecting');
        $this->collector->expects(self::once())
            ->method('getWarnings')
            ->willReturn([]);

        $this->listener->onConsoleTerminate($event);

        self::assertEmpty($output->fetch());
    }
}
