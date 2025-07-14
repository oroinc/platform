<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Event;

use Oro\Bundle\NavigationBundle\Event\JsRoutingDumpListener;
use Oro\Bundle\UIBundle\Asset\DynamicAssetVersionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class JsRoutingDumpListenerTest extends TestCase
{
    private DynamicAssetVersionManager&MockObject $assetVersionManager;
    private JsRoutingDumpListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->assetVersionManager = $this->createMock(DynamicAssetVersionManager::class);

        $this->listener = new JsRoutingDumpListener($this->assetVersionManager);
    }

    private function getEvent(string $commandName): ConsoleTerminateEvent
    {
        $command = $this->createMock(Command::class);
        $command->expects(self::once())
            ->method('getName')
            ->willReturn($commandName);

        $input = $this->createMock(InputInterface::class);
        $input->expects(self::never())
            ->method(self::anything());

        $output = $this->createMock(OutputInterface::class);
        $output->expects(self::never())
            ->method(self::anything());

        return new ConsoleTerminateEvent($command, $input, $output, 0);
    }

    public function testOnConsoleTerminateForUnsupportedCommand(): void
    {
        $this->assetVersionManager->expects(self::never())
            ->method('updateAssetVersion');

        $this->listener->onConsoleTerminate($this->getEvent('test'));
    }

    public function testOnConsoleTerminateForSupportedCommand(): void
    {
        $this->assetVersionManager->expects(self::once())
            ->method('updateAssetVersion')
            ->with('routing');

        $this->listener->onConsoleTerminate($this->getEvent('fos:js-routing:dump'));
    }
}
