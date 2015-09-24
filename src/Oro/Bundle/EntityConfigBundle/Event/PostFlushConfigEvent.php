<?php

namespace Oro\Bundle\EntityConfigBundle\Event;

use Oro\Bundle\EntityConfigBundle\Entity\AbstractConfigModel;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class PostFlushConfigEvent extends Event
{
    /** @var AbstractConfigModel[] */
    protected $models;

    /**
     * @param AbstractConfigModel[] $models        Flushed entity and field config models
     * @param ConfigManager         $configManager The entity config manager
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
}
