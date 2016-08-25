<?php

namespace Oro\Bundle\TranslationBundle\ImportExport\Strategy;

use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;
use Oro\Bundle\TranslationBundle\Entity\Translation;

class TranslationImportStrategy extends ConfigurableAddOrReplaceStrategy
{
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
}
