<?php

namespace Oro\Bundle\TranslationBundle\ImportExport\Strategy;

use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache;

class TranslationImportStrategy extends ConfigurableAddOrReplaceStrategy
{
    /** @var DynamicTranslationMetadataCache */
    protected $metadataCache;

    /**
     * {@inheritdoc}
     * @param Translation $entity
     */
    protected function findExistingEntity($entity, array $searchContext = [])
    {
        return $this->databaseHelper->findOneBy(
            Translation::class,
            [
               'locale' => $entity->getLocale(),
               'domain' => $entity->getDomain(),
               'key' => $entity->getKey(),
            ]
        );
    }

    /**
     * {@inheritdoc}
     * @param Translation $entity
     */
    protected function validateAndUpdateContext($entity)
    {
        $this->metadataCache->updateTimestamp($entity->getLocale());

        return parent::validateAndUpdateContext($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function setMetadataCache(DynamicTranslationMetadataCache $metadataCache)
    {
        $this->metadataCache = $metadataCache;
    }
}
