<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class FiltersConfigLoader extends AbstractConfigLoader
{
    /** @var array */
    protected $fieldMethodMap = [
        FilterFieldConfig::EXCLUDE     => 'setExcluded',
        FilterFieldConfig::ALLOW_ARRAY => 'setArrayAllowed',
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
                $this->loadConfigValue($filter, $key, $value, $this->fieldMethodMap);
            }
        }

        return $filter;
    }
}
