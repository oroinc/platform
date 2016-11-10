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
