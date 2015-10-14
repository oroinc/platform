<?php

namespace Oro\Bundle\EntityConfigBundle\Event;

use Symfony\Component\EventDispatcher\Event as SymfonyEvent;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

abstract class Event extends SymfonyEvent
{
    /** @var ConfigManager */
    protected $configManager;

    /**
     * @return ConfigManager
     */
    public function getConfigManager()
    {
        return $this->configManager;
    }
}
