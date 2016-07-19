<?php

namespace Oro\Bundle\ApiBundle\Util;

use Oro\Component\EntitySerializer\ConfigNormalizer as BaseConfigNormalizer;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig as FieldConfig;

class ConfigNormalizer extends BaseConfigNormalizer
{
    /**
     * {@inheritdoc}
     */
    public function normalizeConfig(array $config)
    {
        if (!empty($config[ConfigUtil::FIELDS])) {
            foreach ($config[ConfigUtil::FIELDS] as $fieldName => $field) {
                if (is_array($field)
                    && !empty($field[FieldConfig::DEPENDS_ON])
                    && !ConfigUtil::isExclude($field)
                ) {
                    $this->processDependentFields($config, $field[FieldConfig::DEPENDS_ON]);
                }
            }
        }

        return parent::normalizeConfig($config);
    }

    /**
     * @param array    $config
     * @param string[] $dependsOnFieldNames
     */
    protected function processDependentFields(array &$config, array $dependsOnFieldNames)
    {
        foreach ($dependsOnFieldNames as $dependsOnFieldName) {
            if (array_key_exists($dependsOnFieldName, $config[ConfigUtil::FIELDS])
                && is_array($config[ConfigUtil::FIELDS][$dependsOnFieldName])
                && ConfigUtil::isExclude($config[ConfigUtil::FIELDS][$dependsOnFieldName])
            ) {
                $config[ConfigUtil::FIELDS][$dependsOnFieldName][ConfigUtil::EXCLUDE] = false;
                if (!empty($config[ConfigUtil::FIELDS][$dependsOnFieldName][FieldConfig::DEPENDS_ON])) {
                    $this->processDependentFields(
                        $config,
                        $config[ConfigUtil::FIELDS][$dependsOnFieldName][FieldConfig::DEPENDS_ON]
                    );
                }
            }
        }
    }
}
