<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Event;

use Oro\Bundle\NavigationBundle\Event\JsRoutingDumpListener;
use Oro\Bundle\UIBundle\Asset\DynamicAssetVersionManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class JsRoutingDumpListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DynamicAssetVersionManager|\PHPUnit\Framework\MockObject\MockObject */
    private $assetVersionManager;

    /** @var JsRoutingDumpListener */
    private $listener;

    protected function setUp(): void
    {
        $this->assetVersionManager = $this->createMock(DynamicAssetVersionManager::class);

        $this->listener = new JsRoutingDumpListener($this->assetVersionManager);
    }

    private function getEvent(string $commandName): ConsoleCommandEvent
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

        return new ConsoleCommandEvent($command, $input, $output);
    }

    public function testOnConsoleCommandForUnsupportedCommand(): void
    {
        $this->assetVersionManager->expects(self::never())
            ->method('updateAssetVersion');

        $this->listener->onConsoleCommand($this->getEvent('test'));
    }

    public function testOnConsoleCommandForSupportedCommand(): void
    {
        $this->assetVersionManager->expects(self::once())
            ->method('updateAssetVersion')
            ->with('routing');

        $this->listener->onConsoleCommand($this->getEvent('fos:js-routing:dump'));
    }
}
