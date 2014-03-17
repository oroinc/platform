<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

class ExtendOptionsManager
{
    const ENTITY_CLASS_OPTION = '_entity_class';
    const TYPE_OPTION         = '_type';
    const MODE_OPTION         = '_mode';
    const TARGET_OPTION       = '_target';
    const NEW_NAME_OPTION     = '_new_name';

    /**
     * @var array key = [table name] or [table name!column name]
     */
    protected $options = [];

    /**
     * Sets table options
     *
     * @param string $tableName
     * @param array  $options
     */
    public function setTableOptions($tableName, array $options)
    {
        $this->setOptions($tableName, $options);
    }

    /**
     * Sets table mode
     *
     * @param string $tableName
     * @param string $mode
     */
    public function setTableMode($tableName, $mode)
    {
        $this->setOptions($tableName, [self::MODE_OPTION => $mode]);
    }

    /**
     * Sets column options
     *
     * @param string $tableName
     * @param string $columnName
     * @param array  $options
     */
    public function setColumnOptions($tableName, $columnName, array $options)
    {
        $this->setOptions(sprintf('%s!%s', $tableName, $columnName), $options);
    }

    /**
     * Sets column mode
     *
     * @param string $tableName
     * @param string $columnName
     * @param string $mode
     */
    public function setColumnMode($tableName, $columnName, $mode)
    {
        $this->setOptions(sprintf('%s!%s', $tableName, $columnName), [self::MODE_OPTION => $mode]);
    }

    /**
     * Sets column type
     *
     * @param string $tableName
     * @param string $columnName
     * @param string $columnType
     */
    public function setColumnType($tableName, $columnName, $columnType)
    {
        $this->setOptions(sprintf('%s!%s', $tableName, $columnName), [self::TYPE_OPTION => $columnType]);
    }

    /**
     * Gets all options
     *
     * @return array
     */
    public function getExtendOptions()
    {
        return $this->options;
    }

    /**
     * @param string $objectKey
     * @param array  $options
     * @throws \InvalidArgumentException
     */
    protected function setOptions($objectKey, array $options)
    {
        if (!isset($this->options[$objectKey])) {
            $this->options[$objectKey] = [];
        }
        foreach ($options as $scope => $values) {
            if (!is_string($scope) || empty($scope)) {
                throw new \InvalidArgumentException(
                    sprintf('A scope name must be non empty string. Key: %s.', $objectKey)
                );
            }
            // a scope which name starts with '_' is a temporary and it should be removed in ExtendOptionsBuilder
            if (strpos($scope, '_') !== 0 && !is_array($values)) {
                throw new \InvalidArgumentException(
                    sprintf('A value of "%s" scope must be an array. Key: %s.', $scope, $objectKey)
                );
            }
            if (isset($this->options[$objectKey][$scope])) {
                foreach ($values as $key => $val) {
                    $this->options[$objectKey][$scope][$key] = $val;
                }
            } else {
                $this->options[$objectKey][$scope] = $values;
            }
        }
    }
}
