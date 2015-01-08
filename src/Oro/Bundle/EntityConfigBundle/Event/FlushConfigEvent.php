<?php

namespace Oro\Bundle\EntityConfigBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\EntityConfigBundle\Entity\AbstractConfigModel;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class FlushConfigEvent extends Event
{
    /** @var AbstractConfigModel[] */
    protected $models;

    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param AbstractConfigModel[] $models
     * @param ConfigManager         $configManager
     */
    public function __construct(array $models, ConfigManager $configManager)
    {
        $this->models        = $models;
        $this->configManager = $configManager;
    }

    /**
     * @return AbstractConfigModel[]
     */
    public function getModels()
    {
        return $this->models;
    }

    /**
     * @return ConfigManager
     */
    public function getConfigManager()
    {
        return $this->configManager;
    }
}
