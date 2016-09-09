<?php

namespace Oro\Bundle\TranslationBundle\ImportExport\Strategy;

use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;

class TranslationImportStrategy extends ConfigurableAddOrReplaceStrategy
{
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
     * {@inheritdoc}
     * @param Translation $entity
     */
    protected function findExistingEntity($entity, array $searchContext = [])
    {
        return $this->translationManager->findValue(
            $entity->getKey(),
            $entity->getLanguage()->getCode(),
            $entity->getDomain()
        );
    }
}
