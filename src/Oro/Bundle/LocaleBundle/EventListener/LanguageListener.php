<?php

namespace Oro\Bundle\LocaleBundle\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache;
use Oro\Bundle\TranslationBundle\Translation\TranslationStatusInterface;

class LanguageListener
{
    /** @var DynamicTranslationMetadataCache */
    protected $metadataCache;

    public function __construct(DynamicTranslationMetadataCache $metadataCache)
    {
        $this->metadataCache = $metadataCache;
    }

    /**
     * @param ConfigUpdateEvent $event
     */
    public function onConfigUpdate(ConfigUpdateEvent $event)
    {
        if ($event->isChanged(TranslationStatusInterface::META_CONFIG_KEY)) {
            $changed = [];

            $newValues = (array)$event->getNewValue(TranslationStatusInterface::META_CONFIG_KEY);
            $oldValues = (array)$event->getOldValue(TranslationStatusInterface::META_CONFIG_KEY);

            /**
             * If last build date meta information updated
             * Than assume that language was update or downloaded
             */
            foreach ($newValues as $lang => $data) {
                if (!isset($oldValues[$lang], $oldValues[$lang]['lastBuildDate'])) {
                    $changed[] = $lang;
                } elseif ($oldValues[$lang]['lastBuildDate'] !== $data['lastBuildDate']) {
                    $changed[] = $lang;
                }
            }
        }

        foreach ($changed as $lang) {
            $this->metadataCache->updateTimestamp($lang);
        }
    }
}
