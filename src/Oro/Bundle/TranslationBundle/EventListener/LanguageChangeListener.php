<?php

namespace Oro\Bundle\TranslationBundle\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache;

class LanguageChangeListener
{
    /** @var DynamicTranslationMetadataCache */
    protected $dbTranslationMetadataCache;

    /**
     * @param DynamicTranslationMetadataCache $dbTranslationMetadataCache
     */
    public function __construct(DynamicTranslationMetadataCache $dbTranslationMetadataCache)
    {
        $this->dbTranslationMetadataCache = $dbTranslationMetadataCache;
    }

    /**
     * @param ConfigUpdateEvent $event
     */
    public function onConfigUpdate(ConfigUpdateEvent $event)
    {
        if (!$event->isChanged('oro_locale.language')) {
            return;
        }

        // mark translation cache dirty
        $this->dbTranslationMetadataCache->updateTimestamp(
            $event->getNewValue('oro_locale.language')
        );
    }
}
