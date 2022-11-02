<?php

namespace Oro\Bundle\ConfigBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;

/**
 * Transforms an entity ID to an entity object and vise versa for a specified configuration option.
 */
class EntitySystemConfigListener
{
    private ManagerRegistry $doctrine;
    private string $entityClass;
    private string $configKey;

    public function __construct(ManagerRegistry $doctrine, string $entityClass, string $configKey)
    {
        $this->doctrine = $doctrine;
        $this->entityClass = $entityClass;
        $this->configKey = $configKey;
    }

    public function onFormPreSetData(ConfigSettingsUpdateEvent $event): void
    {
        /** @var string $settingsKey */
        $settingsKey = str_replace(
            ConfigManager::SECTION_MODEL_SEPARATOR,
            ConfigManager::SECTION_VIEW_SEPARATOR,
            $this->configKey
        );
        $settings = $event->getSettings();
        if (isset($settings[$settingsKey]['value'])) {
            $settings[$settingsKey]['value'] = $this->findEntity($settings[$settingsKey]['value']);
            $event->setSettings($settings);
        }
    }

    public function onSettingsSaveBefore(ConfigSettingsUpdateEvent $event): void
    {
        $settings = $event->getSettings();
        if (isset($settings[$this->configKey]['value'])) {
            $value = $settings[$this->configKey]['value'];
            if (is_a($value, $this->entityClass)) {
                $settings[$this->configKey]['value'] = $this->getEntityId($value);
                $event->setSettings($settings);
            }
        }
    }

    private function findEntity(mixed $id): ?object
    {
        return $this->doctrine->getManagerForClass($this->entityClass)
            ->find($this->entityClass, $id);
    }

    private function getEntityId(object $entity): mixed
    {
        $identifierValues = $this->doctrine->getManagerForClass($this->entityClass)
            ->getClassMetadata($this->entityClass)
            ->getIdentifierValues($entity);

        return reset($identifierValues);
    }
}
