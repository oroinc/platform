<?php

namespace Oro\Bundle\EmailBundle\EventListener\Activity;

use Oro\Bundle\ActivityBundle\Event\FilterTargetClassesEvent;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class FilterTargetClasssesListener
{
    const OWNER_SYSTEM = 'System';

    /** @var ConfigManager */
    protected $entityConfigManager;

    /**
     * @param ConfigManager $entityConfigManager
     */
    public function __construct(ConfigManager $entityConfigManager)
    {
        $this->entityConfigManager = $entityConfigManager;
    }

    public function onFilterTargetClasses(FilterTargetClassesEvent $event)
    {
        $filters = $event->getFilters();

        if (array_key_exists('skip_custom_entity', $filters)) {
            if ($filters['skip_custom_entity']) {
                $targets = $event->getTargetClasses();
                foreach ($targets as $key => $targetClass) {
                    if ($this->entityConfigManager->hasConfig($key)) {
                        $config = $this->entityConfigManager->getEntityConfig('extend', $key);

                        if ($config->get('owner') !== static::OWNER_SYSTEM) {
                            unset($targets[$key]);
                        }
                    }
                }
                $event->setTargetClasses($targets);
            }

            $filters = $filters['criteria'];
            $event->setFilters($filters);
        }
    }
}
