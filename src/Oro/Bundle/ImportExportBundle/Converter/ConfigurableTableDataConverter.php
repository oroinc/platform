<?php

namespace Oro\Bundle\ImportExportBundle\Converter;

use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;
use Oro\Bundle\ImportExportBundle\Processor\EntityNameAwareInterface;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;

class ConfigurableTableDataConverter extends AbstractTableDataConverter implements EntityNameAwareInterface
{
    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var EntityFieldProvider
     */
    protected $fieldProvider;

    /**
     * @var ConfigProviderInterface
     */
    protected $configProvider;

    /**
     * @param EntityFieldProvider $fieldProvider
     * @param ConfigProviderInterface $configProvider
     */
    public function __construct(EntityFieldProvider $fieldProvider, ConfigProviderInterface $configProvider)
    {
        $this->fieldProvider = $fieldProvider;
        $this->configProvider = $configProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function getHeaderConversionRules()
    {
        $this->assertEntityName();

        return $this->getEntityRules($this->entityName, true);
    }

    /**
     * {@inheritdoc}
     */
    protected function getBackendHeader()
    {
        return array_values($this->receiveHeaderConversionRules());
    }

    /**
     * {@inheritdoc}
     */
    public function setEntityName($entityName)
    {
        $this->entityName = $entityName;
    }

    /**
     * @throws LogicException
     */
    protected function assertEntityName()
    {
        if (!$this->entityName) {
            throw new LogicException('Entity class for data converter is not specified');
        }
    }

    /**
     * @param string $entityName
     * @param string $fieldName
     * @param string $parameter
     * @return mixed|null
     */
    protected function getConfigValue($entityName, $fieldName, $parameter)
    {
        if (!$this->configProvider->hasConfig($entityName, $fieldName)) {
            return null;
        }

        $fieldConfig = $this->configProvider->getConfig($entityName, $fieldName);
        if (!$fieldConfig->has($parameter)) {
            return null;
        }

        return $fieldConfig->get($parameter);
    }

    /**
     * @param string $entityName
     * @param bool $fullData
     * @return array
     */
    protected function getEntityRules($entityName, $fullData = false)
    {
        $fields = $this->fieldProvider->getFields($entityName);

        // get fields data
        $rules = array();
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            if (!$fullData && !$this->getConfigValue($entityName, $fieldName, 'identity')) {
                continue;
            }

            if ($this->getConfigValue($entityName, $fieldName, 'excluded')) {
                continue;
            }

            $fieldHeader = $this->getConfigValue($entityName, $fieldName, 'header');
            if (!$fieldHeader) {
                $fieldHeader = $field['label'];
            };

            $fieldOrder = $this->getConfigValue($entityName, $fieldName, 'order');
            if ($fieldOrder === null || $fieldOrder === '') {
                $fieldOrder = 10000;
            }

            $rules[$fieldHeader] = array('name' => $fieldName, 'order' => (int)$fieldOrder);
        }

        return $this->sortRules($rules);
    }

    /**
     * Uses key "order" to sort rules
     *
     * @param array $rules
     * @return array
     */
    protected function sortRules(array $rules)
    {
        // sort fields by order
        uasort($rules, function ($a, $b) {
            return $a['order'] > $b['order'] ? 1 : -1;
        });

        // clear unused data
        foreach ($rules as $label => $data) {
            $rules[$label] = $data['name'];
        }

        return $rules;
    }
}
