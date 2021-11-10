<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Event;

use Oro\Bundle\NavigationBundle\Event\JsRoutingDumpListener;
use Oro\Bundle\UIBundle\Asset\DynamicAssetVersionManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class JsRoutingDumpListenerTest extends \PHPUnit\Framework\TestCase
{
    private const PROJECT_DIR = __DIR__;

    /** @var DynamicAssetVersionManager|\PHPUnit\Framework\MockObject\MockObject */
    private $assetVersionManager;

    /** @var JsRoutingDumpListener */
    private $listener;

    protected function setUp(): void
    {
        $this->assetVersionManager = $this->createMock(DynamicAssetVersionManager::class);

        $this->listener = new JsRoutingDumpListener($this->assetVersionManager, self::PROJECT_DIR, 'prefix');
    }

    public function testOnConsoleCommandForUnsupportedCommand(): void
    {
        $this->assetVersionManager->expects($this->never())
            ->method($this->anything());

        $input = $this->createMock(InputInterface::class);
        $input->expects($this->never())
            ->method($this->anything());

        $inputDefinition = $this->createMock(InputDefinition::class);
        $inputDefinition->expects($this->never())
            ->method($this->anything());

        $this->listener->onConsoleCommand($this->getEvent('test', $input, $inputDefinition));
    }

    public function testOnConsoleCommand(): void
    {
        $this->assetVersionManager->expects($this->once())
            ->method('updateAssetVersion')
            ->with('routing');

        $input = $this->createMock(InputInterface::class);
        $input->expects($this->once())
            ->method('getOption')
            ->willReturn('json');

        $formatOption = $this->createMock(InputOption::class);
        $formatOption->expects($this->once())
            ->method('setDefault')
            ->with('json');

        $targetOption = $this->createMock(InputOption::class);
        $targetOption->expects($this->once())
            ->method('setDefault')
            ->with(implode(DIRECTORY_SEPARATOR, [self::PROJECT_DIR, 'public', 'media', 'js', 'prefix_routes.json']));

        $inputDefinition = $this->createMock(InputDefinition::class);
        $inputDefinition->expects($this->any())
            ->method('getOption')
            ->willReturnMap([
                ['format', $formatOption],
                ['target', $targetOption],
            ]);

        $this->listener->onConsoleCommand($this->getEvent('fos:js-routing:dump', $input, $inputDefinition));
    }

    private function getEvent(string $commandName, InputInterface $input, InputDefinition $def): ConsoleCommandEvent
    {
        $command = $this->createMock(Command::class);
        $command->expects($this->once())
            ->method('getName')
            ->willReturn($commandName);
        $command->expects($this->any())
            ->method('getDefinition')
            ->willReturn($def);

        $output = $this->createMock(OutputInterface::class);
        $output->expects($this->never())
            ->method($this->anything());

        return new ConsoleCommandEvent($command, $input, $output);
    }
}
