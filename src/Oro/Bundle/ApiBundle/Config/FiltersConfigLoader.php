<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * The loader for "filters" configuration section.
 */
class FiltersConfigLoader extends AbstractConfigLoader
{
    private const FIELD_METHOD_MAP = [
        ConfigUtil::EXCLUDE     => 'setExcluded',
        ConfigUtil::COLLECTION  => 'setIsCollection',
        ConfigUtil::ALLOW_ARRAY => 'setArrayAllowed',
        ConfigUtil::ALLOW_RANGE => 'setRangeAllowed'
    ];

    /**
     * {@inheritdoc}
     */
    public function load(array $config)
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

    /**
     * @param FiltersConfig $filters
     * @param array|null    $fields
     */
    protected function loadFields(FiltersConfig $filters, array $fields = null)
    {
        if (!empty($fields)) {
            foreach ($fields as $name => $config) {
                $filters->addField($name, $this->loadField($config));
            }
        }
    }

    /**
     * @param array|null $config
     *
     * @return FilterFieldConfig
     */
    protected function loadField(array $config = null)
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
