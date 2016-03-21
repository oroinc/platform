<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class FiltersConfigLoader extends AbstractConfigLoader
{
    /** @var array */
    protected $methodMap = [
        FiltersConfig::EXCLUSION_POLICY => 'setExclusionPolicy',
    ];

    /** @var array */
    protected $fieldMethodMap = [
        FilterFieldConfig::EXCLUDE       => 'setExcluded',
        FilterFieldConfig::PROPERTY_PATH => 'setPropertyPath',
        FilterFieldConfig::DATA_TYPE     => 'setDataType',
        FilterFieldConfig::ALLOW_ARRAY   => 'setArrayAllowed',
        FilterFieldConfig::DEFAULT_VALUE => 'setDefaultValue',
        FilterFieldConfig::DESCRIPTION   => 'setDescription',
    ];

    /**
     * {@inheritdoc}
     */
    public function load(array $config)
    {
        $filters = new FiltersConfig();

        foreach ($config as $key => $value) {
            if (isset($this->methodMap[$key])) {
                $this->callSetter($filters, $this->methodMap[$key], $value);
            } elseif (ConfigUtil::FIELDS === $key) {
                $this->loadFields($filters, $value);
            } else {
                $this->setValue($filters, $key, $value);
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
                if (isset($this->fieldMethodMap[$key])) {
                    $this->callSetter($filter, $this->fieldMethodMap[$key], $value);
                } else {
                    $this->setValue($filter, $key, $value);
                }
            }
        }

        return $filter;
    }
}
