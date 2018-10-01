<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Gedmo\Exception\RuntimeException;
use Gedmo\Translatable\TranslatableListener;

/**
 * A "hint" is taken into account when creating a hash key for the "query cache".
 * Ensures that the cached 'query' has a dependency on the current localization and returns the result for а
 * current localization.
 */
trait TranslatableQueryTrait
{
    /**
     * @param AbstractQuery $query
     * @param EntityManager $entityManager
     */
    private function addTranslatableLocaleHint(AbstractQuery $query, EntityManager $entityManager)
    {
        $locale = $this->getTranslatableListener($entityManager)
            ->getListenerLocale();

        $query->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $locale);
    }

    /**
     * Get the currently used TranslatableListener
     *
     * @param EntityManager $entityManager
     * @return TranslatableListener
     */
    private function getTranslatableListener(EntityManager $entityManager)
    {
        $allListeners = $entityManager->getEventManager()
            ->getListeners();

        foreach ($allListeners as $eventListeners) {
            foreach ($eventListeners as $listener) {
                if ($listener instanceof TranslatableListener) {
                    return $listener;
                }
            }
        }

        throw new RuntimeException('The translation listener could not be found');
    }
}
