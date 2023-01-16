<?php

namespace Oro\Bundle\ApiBundle\Util;

use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Component\EntitySerializer\ConfigNormalizer as BaseConfigNormalizer;

/**
 * This class should be synchronized with the config normalizer for ObjectNormalizer.
 * @see \Oro\Bundle\ApiBundle\Normalizer\ConfigNormalizer
 */
class ConfigNormalizer extends BaseConfigNormalizer
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function doNormalizeConfig(array $config): array
    {
        if (!empty($config[ConfigUtil::FIELDS])) {
            $toRemove = [];
            foreach ($config[ConfigUtil::FIELDS] as $fieldName => $field) {
                if (!\is_array($field)) {
                    continue;
                }
                if ($this->isExtendedAssociation($field)) {
                    $field[ConfigUtil::PROPERTY_PATH] = ConfigUtil::IGNORE_PROPERTY_PATH;
                }
                if (!empty($field[ConfigUtil::PROPERTY_PATH])) {
                    $propertyPath = $field[ConfigUtil::PROPERTY_PATH];
                    if (ConfigUtil::IGNORE_PROPERTY_PATH === $propertyPath) {
                        if (!isset($field[ConfigUtil::ASSOCIATION_QUERY])) {
                            $toRemove[] = $fieldName;
                        }
                    } elseif (!ConfigUtil::isExclude($field)) {
                        $this->processDependentFields($config, [$propertyPath]);
                    }
                }
                if (!empty($field[ConfigUtil::DEPENDS_ON]) && !ConfigUtil::isExclude($field)) {
                    $this->processDependentFields($config, $field[ConfigUtil::DEPENDS_ON]);
                }
            }
            foreach ($toRemove as $fieldName) {
                unset($config[ConfigUtil::FIELDS][$fieldName]);
            }
        }

        $config = parent::doNormalizeConfig($config);
        if (isset($config[ConfigUtil::RENAMED_FIELDS][ConfigUtil::IGNORE_PROPERTY_PATH])) {
            unset($config[ConfigUtil::RENAMED_FIELDS][ConfigUtil::IGNORE_PROPERTY_PATH]);
            if (empty($config[ConfigUtil::RENAMED_FIELDS])) {
                unset($config[ConfigUtil::RENAMED_FIELDS]);
            }
        }

        return $config;
    }

    /**
     * @param array    $config
     * @param string[] $dependsOn
     */
    protected function processDependentFields(array &$config, array $dependsOn): void
    {
        foreach ($dependsOn as $dependsOnPropertyPath) {
            $this->processDependentField($config, ConfigUtil::explodePropertyPath($dependsOnPropertyPath));
        }
    }

    /**
     * @param array    $config
     * @param string[] $dependsOnPropertyPath
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function processDependentField(array &$config, array $dependsOnPropertyPath): void
    {
        if (!\array_key_exists(ConfigUtil::FIELDS, $config)) {
            $config[ConfigUtil::FIELDS] = [];
        }

        $dependsOnFieldName = $this->findFieldByPropertyPath($config[ConfigUtil::FIELDS], $dependsOnPropertyPath[0]);
        if (!$dependsOnFieldName) {
            if (\count($dependsOnPropertyPath) > 1) {
                return;
            }
            $dependsOnFieldName = $dependsOnPropertyPath[0];
        }

        if (!\array_key_exists($dependsOnFieldName, $config[ConfigUtil::FIELDS])) {
            $config[ConfigUtil::FIELDS][$dependsOnFieldName] = \count($dependsOnPropertyPath) > 1 ? [] : null;
        }
        if (\is_array($config[ConfigUtil::FIELDS][$dependsOnFieldName])) {
            if (ConfigUtil::isExclude($config[ConfigUtil::FIELDS][$dependsOnFieldName])) {
                $config[ConfigUtil::FIELDS][$dependsOnFieldName][ConfigUtil::EXCLUDE] = false;
                if (!empty($config[ConfigUtil::FIELDS][$dependsOnFieldName][ConfigUtil::DEPENDS_ON])) {
                    $this->processDependentFields(
                        $config,
                        $config[ConfigUtil::FIELDS][$dependsOnFieldName][ConfigUtil::DEPENDS_ON]
                    );
                }
            }
            if (\count($dependsOnPropertyPath) > 1) {
                $this->processDependentField(
                    $config[ConfigUtil::FIELDS][$dependsOnFieldName],
                    \array_slice($dependsOnPropertyPath, 1)
                );
            }
        }
    }

    private function isExtendedAssociation(array $config): bool
    {
        return
            !empty($config[ConfigUtil::DATA_TYPE])
            && DataType::isExtendedAssociation($config[ConfigUtil::DATA_TYPE]);
    }
}
