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
     */
    protected function doNormalizeConfig(array $config)
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
                if ($this->isIgnoredField($field)) {
                    $toRemove[] = $fieldName;
                }
                if (!empty($field[ConfigUtil::DEPENDS_ON]) && !ConfigUtil::isExclude($field)) {
                    $this->processDependentFields($config, $field[ConfigUtil::DEPENDS_ON]);
                }
            }
            foreach ($toRemove as $fieldName) {
                unset($config[ConfigUtil::FIELDS][$fieldName]);
            }
        }

        return parent::doNormalizeConfig($config);
    }

    /**
     * @param array    $config
     * @param string[] $dependsOn
     */
    protected function processDependentFields(array &$config, array $dependsOn)
    {
        foreach ($dependsOn as $dependsOnPropertyPath) {
            $this->processDependentField($config, ConfigUtil::explodePropertyPath($dependsOnPropertyPath));
        }
    }

    /**
     * @param array    $config
     * @param string[] $dependsOnPropertyPath
     */
    protected function processDependentField(array &$config, array $dependsOnPropertyPath)
    {
        if (!\array_key_exists(ConfigUtil::FIELDS, $config)) {
            $config[ConfigUtil::FIELDS] = [];
        }
        $dependsOnFieldName = $this->findFieldByPropertyPath(
            $config[ConfigUtil::FIELDS],
            $dependsOnPropertyPath[0]
        );
        if (!$dependsOnFieldName) {
            return;
        }

        if (!\array_key_exists($dependsOnFieldName, $config[ConfigUtil::FIELDS])) {
            $config[ConfigUtil::FIELDS][$dependsOnFieldName] = \count($dependsOnPropertyPath) > 1
                ? []
                : null;
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

    /**
     * @param array $config The config of a field
     *
     * @return bool
     */
    protected function isIgnoredField(array $config)
    {
        return
            !empty($config[ConfigUtil::PROPERTY_PATH])
            && ConfigUtil::IGNORE_PROPERTY_PATH === $config[ConfigUtil::PROPERTY_PATH];
    }

    /**
     * @param array $config The config of a field
     *
     * @return bool
     */
    protected function isExtendedAssociation(array $config)
    {
        return
            !empty($config[ConfigUtil::DATA_TYPE])
            && DataType::isExtendedAssociation($config[ConfigUtil::DATA_TYPE]);
    }
}
