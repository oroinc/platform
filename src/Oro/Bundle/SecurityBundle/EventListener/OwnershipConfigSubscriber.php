<?php

namespace Oro\Bundle\SecurityBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\EntityConfigBundle\Event\Events;
use Oro\Bundle\EntityConfigBundle\Event\PersistConfigEvent;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface;

class OwnershipConfigSubscriber implements EventSubscriberInterface
{
    /** @var MetadataProviderInterface */
    protected $provider;

    /**
     * Constructor
     *
     * @param MetadataProviderInterface $provider
     */
    public function __construct(MetadataProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::PRE_PERSIST_CONFIG => 'prePersistEntityConfig'
        ];
    }

    /**
     * @param PersistConfigEvent $event
     */
    public function prePersistEntityConfig(PersistConfigEvent $event)
    {
        $config   = $event->getConfig();
        $configId = $config->getId();

        if ($configId->getScope() !== 'extend') {
            return;
        }

        $className = $configId->getClassName();
        $this->provider->clearCache($className);

        $change    = $event->getConfigManager()->getConfigChangeSet($config);
        $isDeleted = isset($change['state']) && $change['state'][1] === ExtendScope::STATE_DELETE;
        if (!$isDeleted) {
            $this->provider->warmUpCache($className);
        }
    }
}
