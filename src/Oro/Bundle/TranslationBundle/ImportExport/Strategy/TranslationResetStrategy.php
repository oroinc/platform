<?php

namespace Oro\Bundle\TranslationBundle\ImportExport\Strategy;

use Oro\Bundle\TranslationBundle\Entity\Translation;

/**
 * Strategy for resetting translations to their default state.
 *
 * Extends the translation import strategy to provide reset-specific functionality.
 * Deletes all existing translations for a language before importing new ones, ensuring
 * a clean reset. Tracks processed languages to avoid duplicate deletions and counts
 * deleted translations for reporting purposes.
 */
class TranslationResetStrategy extends TranslationImportStrategy
{
    /** @var array */
    protected $processedLanguages = [];

    /**
     * @param Translation $entity
     *
     */
    #[\Override]
    protected function beforeProcessEntity($entity)
    {
        if ($entity instanceof Translation) {
            $language = $entity->getLanguage();
            if ($language && empty($this->processedLanguages[$language->getId()])) {
                $repository = $this->getTranslationRepository();

                $this->context->incrementDeleteCount($repository->getCountByLanguage($language));
                $repository->deleteByLanguage($language);

                $this->processedLanguages[$language->getId()] = true;
            }
        }

        return parent::beforeProcessEntity($entity);
    }

    #[\Override]
    protected function findExistingEntity($entity, array $searchContext = [])
    {
        // no need to search entity
        if (is_a($entity, $this->entityName)) {
            return null;
        }

        return parent::findExistingEntity($entity, $searchContext);
    }

    /**
     * There is no replaced entities during reset
     *
     */
    #[\Override]
    protected function updateContextCounters($entity)
    {
        $this->context->incrementAddCount();
    }
}
