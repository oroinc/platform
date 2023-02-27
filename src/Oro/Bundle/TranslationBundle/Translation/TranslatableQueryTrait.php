<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Exception\RuntimeException;
use Gedmo\Translatable\TranslatableListener;

/**
 * A "hint" is taken into account when creating a hash key for the "query cache".
 * Ensures that the cached 'query' has a dependency on the current localization and returns the result for а
 * current localization.
 */
trait TranslatableQueryTrait
{
    private function addTranslatableLocaleHint(AbstractQuery $query, EntityManagerInterface $entityManager): void
    {
        $query->setHint(
            TranslatableListener::HINT_TRANSLATABLE_LOCALE,
            $this->getTranslatableListener($entityManager)->getListenerLocale()
        );
    }

    private function getTranslatableListener(EntityManagerInterface $entityManager): TranslatableListener
    {
        $allListeners = $entityManager->getEventManager()->getListeners();
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
