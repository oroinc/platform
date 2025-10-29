<?php

namespace Oro\Bundle\ConfigBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;

/**
 * Transforms an array of entity IDs to an array of entity objects and vise versa
 * for a specified configuration option.
 */
class EntityCollectionSystemConfigListener
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly string $entityClass,
        private readonly string $configKey
    ) {
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
            $settings[$settingsKey]['value'] = $this->findEntities($settings[$settingsKey]['value']);
            $event->setSettings($settings);
        }
    }

    public function onSettingsSaveBefore(ConfigSettingsUpdateEvent $event): void
    {
        $settings = $event->getSettings();
        if (isset($settings[$this->configKey]['value'])) {
            $value = $settings[$this->configKey]['value'];
            $ids = [];
            foreach ($value as $entity) {
                if (is_a($entity, $this->entityClass)) {
                    $ids[] = $this->getEntityId($entity);
                }
            }
            $settings[$this->configKey]['value'] = $ids;
            $event->setSettings($settings);
        }
    }

    private function findEntities(mixed $ids): array
    {
        $idField = $this->doctrine->getManagerForClass($this->entityClass)
            ->getClassMetadata($this->entityClass)
            ->getIdentifier();

        return $this->doctrine->getRepository($this->entityClass)
            ->findBy([$idField[0] => $ids]);
    }

    private function getEntityId(object $entity): mixed
    {
        $identifierValues = $this->doctrine->getManagerForClass($this->entityClass)
            ->getClassMetadata($this->entityClass)
            ->getIdentifierValues($entity);

        return reset($identifierValues);
    }
}
