<?php

namespace Oro\Bundle\EntityConfigBundle\Event;

use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class PostFlushConfigEvent extends Event
{
    /** @var ConfigModel[] */
    protected $models;

    /**
     * @param ConfigModel[] $models        Flushed entity and field config models
     * @param ConfigManager $configManager The entity config manager
     */
    public function __construct(array $models, ConfigManager $configManager)
    {
        $this->models        = $models;
        $this->configManager = $configManager;
    }

    /**
     * @return ConfigModel[]
     */
    public function getModels()
    {
        return $this->models;
    }
}
