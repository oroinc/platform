<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment;

use Oro\Bundle\ApiBundle\Config\EntityConfigMerger;
use Oro\Bundle\ApiBundle\Provider\ConfigBagInterface;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class TestConfigBag implements ConfigBagInterface
{
    /** @var ConfigBagInterface */
    private $configBag;

    /** @var EntityConfigMerger */
    private $entityConfigMerger;

    /** @var array */
    private $appendedConfig = [];

    /** @var bool */
    private $hasChanges = false;

    public function __construct(
        ConfigBagInterface $configBag,
        EntityConfigMerger $entityConfigMerger
    ) {
        $this->configBag = $configBag;
        $this->entityConfigMerger = $entityConfigMerger;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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

    /**
     * @param string $entityClass
     * @param array  $config
     */
    public function appendEntityConfig($entityClass, array $config)
    {
        $this->appendedConfig['entities'][$entityClass] = $config;
        $this->hasChanges = true;
    }

    /**
     * @return bool
     */
    public function restoreConfigs()
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
