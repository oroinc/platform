<?php

namespace Oro\Bundle\EntityConfigBundle\EventListener;

use Doctrine\Common\Persistence\Event\LoadClassMetadataEventArgs;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class PostLoadClassMetadataDoctrineListener
{
    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param LoadClassMetadataEventArgs $event
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $event)
    {
        $this->configManager->enableEntityCheck();
    }
}
