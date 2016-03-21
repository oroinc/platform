<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class SortersConfigLoader extends AbstractConfigLoader
{
    /** @var array */
    protected $methodMap = [
        SortersConfig::EXCLUSION_POLICY => 'setExclusionPolicy',
    ];

    /** @var array */
    protected $fieldMethodMap = [
        SorterFieldConfig::EXCLUDE       => 'setExcluded',
        SorterFieldConfig::PROPERTY_PATH => 'setPropertyPath',
    ];

    /**
     * {@inheritdoc}
     */
    public function load(array $config)
    {
        $sorters = new SortersConfig();

        foreach ($config as $key => $value) {
            if (isset($this->methodMap[$key])) {
                $this->callSetter($sorters, $this->methodMap[$key], $value);
            } elseif (ConfigUtil::FIELDS === $key) {
                $this->loadFields($sorters, $value);
            } else {
                $this->setValue($sorters, $key, $value);
            }
        }

        return $sorters;
    }

    /**
     * @param SortersConfig $sorters
     * @param array|null    $fields
     */
    protected function loadFields(SortersConfig $sorters, array $fields = null)
    {
        if (!empty($fields)) {
            foreach ($fields as $name => $config) {
                $sorters->addField($name, $this->loadField($config));
            }
        }
    }

    /**
     * @param array|null $config
     *
     * @return SorterFieldConfig
     */
    protected function loadField(array $config = null)
    {
        $sorter = new SorterFieldConfig();
        if (!empty($config)) {
            foreach ($config as $key => $value) {
                if (isset($this->fieldMethodMap[$key])) {
                    $this->callSetter($sorter, $this->fieldMethodMap[$key], $value);
                } else {
                    $this->setValue($sorter, $key, $value);
                }
            }
        }

        return $sorter;
    }
}
