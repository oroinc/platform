<?php

namespace Oro\Bundle\EntityConfigBundle\Event;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Symfony\Contracts\EventDispatcher\Event as SymfonyEvent;

/**
 * Abstract Event class that extends Symfony Event with ConfigManager
 */
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
