<?php

namespace Oro\Bundle\EntityConfigBundle\Event;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Symfony\Component\EventDispatcher\Event as SymfonyEvent;

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
