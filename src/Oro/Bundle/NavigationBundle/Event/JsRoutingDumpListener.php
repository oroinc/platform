<?php

namespace Oro\Bundle\NavigationBundle\Event;

use Symfony\Component\Console\Event\ConsoleCommandEvent;

use Oro\Bundle\UIBundle\Asset\DynamicAssetVersionManager;

class JsRoutingDumpListener
{
    /** @var DynamicAssetVersionManager */
    protected $assetVersionManager;

    /**
     * @param DynamicAssetVersionManager $assetVersionManager
     */
    public function __construct(DynamicAssetVersionManager $assetVersionManager)
    {
        $this->assetVersionManager = $assetVersionManager;
    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        if ('fos:js-routing:dump' === $event->getCommand()->getName()) {
            $this->assetVersionManager->updateAssetVersion('routing');
        }
    }
}
