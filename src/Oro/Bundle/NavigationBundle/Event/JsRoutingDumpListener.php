<?php

namespace Oro\Bundle\NavigationBundle\Event;

use Oro\Bundle\UIBundle\Asset\DynamicAssetVersionManager;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;

/**
 * Renews the "routing" assets version before running the "fos:js-routing:dump" command.
 */
class JsRoutingDumpListener
{
    private DynamicAssetVersionManager $assetVersionManager;

    public function __construct(DynamicAssetVersionManager $assetVersionManager)
    {
        $this->assetVersionManager = $assetVersionManager;
    }

    public function onConsoleTerminate(ConsoleTerminateEvent $event): void
    {
        $command = $event->getCommand();
        if (null === $command || 'fos:js-routing:dump' !== $command->getName()) {
            return;
        }

        $this->assetVersionManager->updateAssetVersion('routing');
    }
}
