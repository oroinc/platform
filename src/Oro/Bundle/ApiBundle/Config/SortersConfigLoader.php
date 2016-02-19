<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class SortersConfigLoader extends AbstractConfigLoader implements ConfigLoaderInterface
{
    /** @var array */
    protected $methodMap = [
        ConfigUtil::EXCLUSION_POLICY => 'setExclusionPolicy',
    ];

    /** @var array */
    protected $fieldMethodMap = [
        ConfigUtil::EXCLUDE       => 'setExcluded',
        ConfigUtil::PROPERTY_PATH => 'setPropertyPath',
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
    protected function loadFields(SortersConfig $sorters, $fields)
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
    protected function loadField($config)
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
