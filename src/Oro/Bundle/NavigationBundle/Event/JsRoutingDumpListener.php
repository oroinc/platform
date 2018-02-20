<?php

namespace Oro\Bundle\NavigationBundle\Event;

use Oro\Bundle\UIBundle\Asset\DynamicAssetVersionManager;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

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
