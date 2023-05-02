<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment;

use Oro\Bundle\ApiBundle\Config\EntityConfigMerger;
use Oro\Bundle\ApiBundle\Provider\ConfigBagInterface;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class TestConfigBag implements ConfigBagInterface
{
    private ConfigBagInterface $configBag;
    private EntityConfigMerger $entityConfigMerger;
    private array $appendedConfig = [];
    private bool $hasChanges = false;

    public function __construct(
        ConfigBagInterface $configBag,
        EntityConfigMerger $entityConfigMerger
    ) {
        $this->configBag = $configBag;
        $this->entityConfigMerger = $entityConfigMerger;
    }

    /**
     * {@inheritDoc}
     */
    public function getClassNames(string $version): array
    {
        $result = $this->configBag->getClassNames($version);
        if (!empty($this->appendedConfig['entities'])) {
            $result = array_unique(array_merge($result, array_keys($this->appendedConfig['entities'])));
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfig(string $className, string $version): ?array
    {
        $result = $this->configBag->getConfig($className, $version);
        if (!empty($this->appendedConfig['entities'][$className])) {
            $result = $this->updateRenamedFields(
                $this->entityConfigMerger->merge($this->appendedConfig['entities'][$className], $result)
            );
        }

        return $result;
    }

    public function appendEntityConfig(string $entityClass, array $config): void
    {
        $this->appendedConfig['entities'][$entityClass] = $config;
        $this->hasChanges = true;
    }

    public function restoreConfigs(): bool
    {
        if (!$this->hasChanges) {
            return false;
        }

        $this->appendedConfig = [];
        $this->hasChanges = false;

        return true;
    }

    private function updateRenamedFields(array $config): array
    {
        if (empty($config[ConfigUtil::FIELDS])) {
            return $config;
        }

        $fields = $this->getFieldsWithPropertyPath($config[ConfigUtil::FIELDS]);
        if (!$fields) {
            return $config;
        }

        $toRemoveFields = [];
        $processedFields = [];
        foreach ($fields as $field) {
            $processedFields[] = $field;
            $propertyPath = $config[ConfigUtil::FIELDS][$field][ConfigUtil::PROPERTY_PATH];
            $toRenameField = $this->findFieldToRename(
                $fields,
                $config[ConfigUtil::FIELDS],
                $propertyPath,
                $processedFields
            );
            if ($toRenameField) {
                $config[ConfigUtil::FIELDS][$toRenameField] = array_merge(
                    $config[ConfigUtil::FIELDS][$field],
                    $config[ConfigUtil::FIELDS][$toRenameField]
                );
                $toRemoveFields[] = $field;
            } elseif (\array_key_exists($propertyPath, $config[ConfigUtil::FIELDS])) {
                $mainFieldConfig = $config[ConfigUtil::FIELDS][$propertyPath] ?? [];
                if (ConfigUtil::IGNORE_PROPERTY_PATH !== ($mainFieldConfig[ConfigUtil::PROPERTY_PATH] ?? null)) {
                    /** @noinspection SlowArrayOperationsInLoopInspection */
                    $config[ConfigUtil::FIELDS][$field] = array_merge(
                        $mainFieldConfig,
                        $config[ConfigUtil::FIELDS][$field]
                    );
                    $toRemoveFields[] = $propertyPath;
                }
            }
        }
        foreach ($toRemoveFields as $field) {
            unset($config[ConfigUtil::FIELDS][$field]);
        }

        return $config;
    }

    private function getFieldsWithPropertyPath(array $fieldConfigs): array
    {
        $fields = [];
        foreach ($fieldConfigs as $field => $fieldConfig) {
            if (empty($fieldConfig[ConfigUtil::PROPERTY_PATH])) {
                continue;
            }
            $propertyPath = $fieldConfig[ConfigUtil::PROPERTY_PATH];
            if (ConfigUtil::IGNORE_PROPERTY_PATH === $propertyPath || $field === $propertyPath) {
                continue;
            }
            $fields[] = $field;
        }

        return $fields;
    }

    private function findFieldToRename(
        array $fields,
        array $fieldConfigs,
        string $propertyPath,
        array $processedFields
    ): ?string {
        foreach ($fields as $field) {
            if ($fieldConfigs[$field][ConfigUtil::PROPERTY_PATH] === $propertyPath
                && !\in_array($field, $processedFields, true)
            ) {
                return $field;
            }
        }

        return null;
    }
}
