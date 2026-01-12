<?php

namespace Oro\Bundle\EntityExtendBundle\Validator;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Compare configuration of custom entities is compatible with database.
 */
class CustomEntityConfigValidatorService
{
    public function __construct(protected ConfigManager $configManager, protected array $customEntities)
    {
    }

    public function checkConfigs(): ?array
    {
        $configs = $this->configManager->getConfigs('extend', null, true);
        $isNotConfigured = [];
        foreach ($configs as $entityConfig) {
            $className = $entityConfig->getId()->getClassName();
            if (
                !ExtendHelper::isCustomEntity($className)
                || str_starts_with($className, ExtendHelper::ENUM_CLASS_NAME_PREFIX)
            ) {
                continue;
            }
            if (!in_array($className, $this->customEntities, true)) {
                $isNotConfigured[] = $className;
            }
        }
        if (!empty($isNotConfigured)) {
            return $this->formatError($isNotConfigured, false);
        }

        return null;
    }

    public function checkConfigExists(string $customEntityClass): void
    {
        if (!in_array($customEntityClass, $this->customEntities, true)) {
            $this->formatError([$customEntityClass]);
        }
    }

    protected function formatError(array $isNotConfigured, bool $throw = true): array
    {
        $entitiesConfig = implode(',' . "\n  - ", $isNotConfigured);
        $message = 'Custom Entity is not configured properly. '
            . 'Please update your `oro_entity_extend.custom_entities` configuration.'
            . "\n"
            . 'List of missing custom entities:'
            . "\n"
            . '  - '
            . $entitiesConfig;

        if (!$throw) {
            return [$message];
        }

        throw new \RuntimeException($message);
    }
}
