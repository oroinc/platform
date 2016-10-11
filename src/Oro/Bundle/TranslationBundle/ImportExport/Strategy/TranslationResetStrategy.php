<?php

namespace Oro\Bundle\TranslationBundle\ImportExport\Strategy;

use Oro\Bundle\TranslationBundle\Entity\Translation;

class TranslationResetStrategy extends TranslationImportStrategy
{
    /** @var array */
    protected $processedLanguages = [];

    /**
     * @param Translation $entity
     *
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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
     * {@inheritdoc}
     */
    protected function updateContextCounters($entity)
    {
        $this->context->incrementAddCount();
    }
}
