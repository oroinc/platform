<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateRendererConfigProviderInterface;
use Oro\Bundle\EntityConfigBundle\Event\PreFlushConfigEvent;

/**
 * Clears email renderer configuration cache when related entity configuration is changed.
 */
class EntityConfigListener
{
    /** @var TemplateRendererConfigProviderInterface */
    private $emailRendererConfigProvider;

    public function __construct(TemplateRendererConfigProviderInterface $emailRendererConfigProvider)
    {
        $this->emailRendererConfigProvider = $emailRendererConfigProvider;
    }

    public function preFlush(PreFlushConfigEvent $event)
    {
        $config = $event->getConfig('email');
        if (null === $config || $event->isEntityConfig()) {
            return;
        }

        $changeSet = $event->getConfigManager()->getConfigChangeSet($config);
        if (isset($changeSet['available_in_template'])) {
            $this->emailRendererConfigProvider->clearCache();
        }
    }
}
