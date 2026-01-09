<?php

namespace Oro\Bundle\EntityConfigBundle\Event;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;

/**
 * Dispatched after entity and field configuration changes have been persisted to the database.
 *
 * This event is triggered after the configuration manager flushes all pending configuration changes,
 * allowing listeners to perform post-flush operations such as cache invalidation or secondary updates
 * based on the persisted configuration models.
 */
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
