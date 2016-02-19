<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class FiltersConfigLoader extends AbstractConfigLoader implements ConfigLoaderInterface
{
    /** @var array */
    protected $methodMap = [
        ConfigUtil::EXCLUSION_POLICY => 'setExclusionPolicy',
    ];

    /** @var array */
    protected $fieldMethodMap = [
        ConfigUtil::EXCLUDE       => 'setExcluded',
        ConfigUtil::PROPERTY_PATH => 'setPropertyPath',
        ConfigUtil::DATA_TYPE     => 'setDataType',
        ConfigUtil::ALLOW_ARRAY   => 'setArrayAllowed',
        ConfigUtil::DEFAULT_VALUE => 'setDefaultValue',
        ConfigUtil::DESCRIPTION   => 'setDescription',
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
    protected function loadFields(FiltersConfig $filters, $fields)
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
    protected function loadField($config)
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
