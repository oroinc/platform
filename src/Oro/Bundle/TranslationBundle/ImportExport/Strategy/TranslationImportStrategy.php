<?php

namespace Oro\Bundle\TranslationBundle\ImportExport\Strategy;

use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;

class TranslationImportStrategy extends ConfigurableAddOrReplaceStrategy
{
    /**
     * {@inheritdoc}
     * @param Translation $entity
     */
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
     * {@inheritdoc}
     * @param Translation $entity
     */
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
