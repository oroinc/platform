<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Oro\Bundle\EntityExtendBundle\Manager\EnumOptionTranslationManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\TranslationBundle\Entity\Translation;

/**
 * Actualizing EnumOptionTranslation entity data on Translation entity changes.
 */
class ActualizeEnumOptionTranslationListener
{
    private array $toUpdate = [];
    private array $toRemove = [];

    public function __construct(
        protected EnumOptionTranslationManager $enumOptionTranslationManager
    ) {
    }

    public function postPersist(Translation $entity): void
    {
        if ($this->isEnumOptionTranslation($entity)) {
            $this->toUpdate[] = $entity;
        }
    }

    public function postUpdate(Translation $entity): void
    {
        if ($this->isEnumOptionTranslation($entity)) {
            $this->toUpdate[] = $entity;
        }
    }

    public function postRemove(Translation $entity): void
    {
        if ($this->isEnumOptionTranslation($entity)) {
            $this->toRemove[] = $entity;
        }
    }

    public function postFlush(): void
    {
        if (empty($this->toUpdate) && empty($this->toRemove)) {
            return;
        }

        foreach ($this->toRemove as $entity) {
            list($id, $name, $locale) = $this->enumOptionTranslationManager->getTranslationData($entity);
            $this->enumOptionTranslationManager->removeEnumOptionTranslation($id, $locale);
        }

        foreach ($this->toUpdate as $entity) {
            list($id, $name, $locale) = $this->enumOptionTranslationManager->getTranslationData($entity);
            $this->enumOptionTranslationManager->actualizeEnumOptionTranslation(
                $id,
                $locale,
                $name
            );
        }

        $this->toRemove = [];
        $this->toUpdate = [];
        $this->enumOptionTranslationManager->flushEnumTranslations();
    }

    private function isEnumOptionTranslation(Translation $entity): bool
    {
        return str_contains($entity->getTranslationKey()->getKey(), ExtendHelper::ENUM_TRANSLATION_PREFIX);
    }
}
