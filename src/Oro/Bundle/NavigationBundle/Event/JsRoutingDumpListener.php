<?php

namespace Oro\Bundle\NavigationBundle\Event;

use Oro\Bundle\UIBundle\Asset\DynamicAssetVersionManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;

/**
 * Renews the "routing" assets version before running the "fos:js-routing:dump" command.
 */
class JsRoutingDumpListener
{
    private DynamicAssetVersionManager $assetVersionManager;

    public function __construct(
        DynamicAssetVersionManager $assetVersionManager,
        string $projectDir,
        string $filenamePrefix
    ) {
        $this->assetVersionManager = $assetVersionManager;
    }

    /**
     * @deprecated use {@see onConsoleTerminate} instead.
     */
    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $this->updateAssetVersion($event->getCommand());
    }

    public function onConsoleTerminate(ConsoleTerminateEvent $event): void
    {
        $this->updateAssetVersion($event->getCommand());
    }

    private function updateAssetVersion(?Command $command): void
    {
        if (null === $command || 'fos:js-routing:dump' !== $command->getName()) {
            return;
        }

        $this->assetVersionManager->updateAssetVersion('routing');
    }
}
