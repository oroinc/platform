<?php

namespace Oro\Bundle\TranslationBundle\ImportExport\Strategy;

use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;

class TranslationResetStrategy extends TranslationImportStrategy
{
    /**
     * @var array
     */
    protected $processedLanguages = [];

    /**
     * @var TranslationManager
     */
    protected $translationManager;

    /**
     * @param TranslationManager $translationManager
     */
    public function setTranslationManager(TranslationManager $translationManager)
    {
        $this->translationManager = $translationManager;
    }

    /**
     * @param Translation $entity
     *
     * {@inheritdoc}
     */
    protected function beforeProcessEntity($entity)
    {
        if ($entity instanceof Translation) {
            $locale = $entity->getLocale();
            if ($locale && empty($this->processedLanguages[$locale])) {
                /** @var TranslationRepository $repository */
                $repository = $this->doctrineHelper->getEntityRepositoryForClass($this->entityName);
                $this->context->incrementDeleteCount(
                    $repository->getCountByLocale($locale)
                );

                $repository->deleteByLocale($locale);

                $this->processedLanguages[$locale] = true;
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
