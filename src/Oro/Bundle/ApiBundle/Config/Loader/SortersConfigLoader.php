<?php

namespace Oro\Bundle\ApiBundle\Config\Loader;

use Oro\Bundle\ApiBundle\Config\SorterFieldConfig;
use Oro\Bundle\ApiBundle\Config\SortersConfig;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * The loader for "sorters" configuration section.
 */
class SortersConfigLoader extends AbstractConfigLoader
{
    private const FIELD_METHOD_MAP = [
        ConfigUtil::PROPERTY_PATH => 'setPropertyPath',
        ConfigUtil::EXCLUDE       => 'setExcluded'
    ];

    /**
     * {@inheritdoc}
     */
    public function load(array $config): mixed
    {
        $sorters = new SortersConfig();
        foreach ($config as $key => $value) {
            if (ConfigUtil::FIELDS === $key) {
                $this->loadFields($sorters, $value);
            } else {
                $this->loadConfigValue($sorters, $key, $value);
            }
        }

        return $sorters;
    }

    private function loadFields(SortersConfig $sorters, ?array $fields): void
    {
        if (!empty($fields)) {
            foreach ($fields as $name => $config) {
                $sorters->addField($name, $this->loadField($config));
            }
        }
    }

    private function loadField(?array $config): SorterFieldConfig
    {
        $sorter = new SorterFieldConfig();
        if (!empty($config)) {
            foreach ($config as $key => $value) {
                $this->loadConfigValue($sorter, $key, $value, self::FIELD_METHOD_MAP);
            }
        }

        return $sorter;
    }
}
