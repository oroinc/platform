<?php

namespace Oro\Bundle\TranslationBundle\ImportExport\Strategy;

use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;

/**
 * Strategy for importing translations into the system.
 *
 * Extends the configurable add-or-replace strategy to handle translation-specific import logic.
 * Manages the import of translation entities, handling cases where translations have not been
 * translated yet by setting their values to null to use parent translations. Locates existing
 * translations by key, language, and domain for proper update or creation.
 */
class TranslationImportStrategy extends ConfigurableAddOrReplaceStrategy
{
    /**
     * @param Translation $entity
     */
    #[\Override]
    public function process($entity)
    {
        $itemData = $this->context->getValue('itemData');
        if (is_array($itemData) && !$itemData['is_translated'] && !$itemData['value']) {
            /**
             * If translation never been translated, that seems for this translation should be used parent translation
             */
            $entity->setValue(null);
        }

        return parent::process($entity);
    }

    /**
     * @param Translation $entity
     */
    #[\Override]
    protected function findExistingEntity($entity, array $searchContext = [])
    {
        return $this->getTranslationRepository()
            ->findTranslation(
                $entity->getTranslationKey()->getKey(),
                $entity->getLanguage()->getCode(),
                $entity->getTranslationKey()->getDomain()
            );
    }

    /**
     * @return TranslationRepository
     */
    protected function getTranslationRepository()
    {
        return $this->doctrineHelper->getEntityRepositoryForClass(Translation::class);
    }
}
