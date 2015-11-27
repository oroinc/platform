<?php

namespace Oro\Bundle\TagBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Event\PostFlushConfigEvent;
use Oro\Bundle\TagBundle\Entity\TagManager;

class EntityConfigListener
{
    /** @var TagManager */
    protected $tagManager;

    /** @param TagManager $tagManager */
    public function __construct(TagManager $tagManager)
    {
        $this->tagManager = $tagManager;
    }

    /** @param PostFlushConfigEvent $event */
    public function postFlush(PostFlushConfigEvent $event)
    {
        foreach ($event->getModels() as $model) {
            if ($model instanceof EntityConfigModel) {
                $configManager = $event->getConfigManager();
                $className = $model->getClassName();
                $changeSet = $configManager->getConfigChangeSet(
                    $configManager->getProvider('tag')->getConfig($className)
                );

                if (isset($changeSet['enabled']) && $changeSet['enabled'][0] && (!$changeSet['enabled'][1])) {
                    $this->tagManager->deleteTags($className);
                }
            }
        }
    }

}
