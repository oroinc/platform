<?php

namespace Oro\Bundle\ApiBundle\Config\Loader;

use Oro\Bundle\ApiBundle\Config\FilterFieldConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * The loader for "filters" configuration section.
 */
class FiltersConfigLoader extends AbstractConfigLoader
{
    private const FIELD_METHOD_MAP = [
        ConfigUtil::FILTER_TYPE      => 'setType',
        ConfigUtil::FILTER_OPERATORS => 'setOperators',
        ConfigUtil::FILTER_OPTIONS   => 'setOptions',
        ConfigUtil::PROPERTY_PATH    => 'setPropertyPath',
        ConfigUtil::DATA_TYPE        => 'setDataType',
        ConfigUtil::DESCRIPTION      => 'setDescription',
        ConfigUtil::EXCLUDE          => 'setExcluded',
        ConfigUtil::COLLECTION       => 'setIsCollection',
        ConfigUtil::ALLOW_ARRAY      => 'setArrayAllowed',
        ConfigUtil::ALLOW_RANGE      => 'setRangeAllowed'
    ];

    /**
     * {@inheritdoc}
     */
    public function load(array $config): mixed
    {
        $filters = new FiltersConfig();
        foreach ($config as $key => $value) {
            if (ConfigUtil::FIELDS === $key) {
                $this->loadFields($filters, $value);
            } else {
                $this->loadConfigValue($filters, $key, $value);
            }
        }

        return $filters;
    }

    private function loadFields(FiltersConfig $filters, ?array $fields): void
    {
        if (!empty($fields)) {
            foreach ($fields as $name => $config) {
                $filters->addField($name, $this->loadField($config));
            }
        }
    }

    private function loadField(?array $config): FilterFieldConfig
    {
        $filter = new FilterFieldConfig();
        if (!empty($config)) {
            foreach ($config as $key => $value) {
                $this->loadConfigValue($filter, $key, $value, self::FIELD_METHOD_MAP);
            }
        }

        return $filter;
    }
}
