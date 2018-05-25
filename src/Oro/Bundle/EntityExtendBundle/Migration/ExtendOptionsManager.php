<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Oro\Component\PhpUtils\ArrayUtil;

class ExtendOptionsManager
{
    const ENTITY_CLASS_OPTION = '_entity_class';
    const FIELD_NAME_OPTION   = '_field';
    const TYPE_OPTION         = '_type';
    const MODE_OPTION         = '_mode';
    const TARGET_OPTION       = '_target';
    const NEW_NAME_OPTION     = '_new_name';
    const APPEND_SECTION      = '_append';

    /**
     * Example of usage: sprintf('%s!%s', $tableName, $columnName)
     */
    const COLUMN_OPTION_FORMAT = '%s!%s';

    /**
     * @var array
     * [
     *      {table name} => [...],
     *      {table name}!{column name} => [...],
     *      self::APPEND_SECTION => [
     *          {table name} => [...],
     *          {table name}!{column name} => [...],
     *      ]
     * ]
     */
    protected $options = [];

    /**
     * Sets table options
     *
     * @param string $tableName
     * @param array  $options
     * @throws \InvalidArgumentException
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
     * @throws \InvalidArgumentException
     */
    public function setTableMode($tableName, $mode)
    {
        $this->setOptions($tableName, [self::MODE_OPTION => $mode]);
    }

    /**
     * Removes table options from the options manager
     *
     * @param string $tableName
     */
    public function removeTableOptions($tableName)
    {
        unset($this->options[$tableName], $this->options[self::APPEND_SECTION][$tableName]);
    }

    /**
     * Sets column options
     *
     * @param string $tableName
     * @param string $columnName
     * @param array  $options
     * @throws \InvalidArgumentException
     */
    public function setColumnOptions($tableName, $columnName, array $options)
    {
        $this->setOptions(sprintf(static::COLUMN_OPTION_FORMAT, $tableName, $columnName), $options);
    }

    /**
     * Sets column mode
     *
     * @param string $tableName
     * @param string $columnName
     * @param string $mode
     * @throws \InvalidArgumentException
     */
    public function setColumnMode($tableName, $columnName, $mode)
    {
        $this->setOptions(sprintf(static::COLUMN_OPTION_FORMAT, $tableName, $columnName), [self::MODE_OPTION => $mode]);
    }

    /**
     * @param string $tableName
     * @param string $columnName
     * @param array  $options
     */
    public function mergeColumnOptions($tableName, $columnName, array $options)
    {
        $objectKey = sprintf(static::COLUMN_OPTION_FORMAT, $tableName, $columnName);
        if (!isset($this->options[$objectKey])) {
            $this->options[$objectKey] = [];
        }
        $this->options[$objectKey] = ArrayUtil::arrayMergeRecursiveDistinct($this->options[$objectKey], $options);
    }

    /**
     * @param string $tableName
     * @param string $columnName
     * @return bool
     */
    public function hasColumnOptions(string $tableName, string $columnName): bool
    {
        $objectKey = sprintf(static::COLUMN_OPTION_FORMAT, $tableName, $columnName);

        return isset($this->options[$objectKey]);
    }

    /**
     * @param string $tableName
     * @param string $columnName
     * @return array
     */
    public function getColumnOptions(string $tableName, string $columnName): array
    {
        $objectKey = sprintf(static::COLUMN_OPTION_FORMAT, $tableName, $columnName);

        return $this->options[$objectKey] ?? [];
    }

    /**
     * Sets column type
     *
     * @param string $tableName
     * @param string $columnName
     * @param string $columnType
     * @throws \InvalidArgumentException
     */
    public function setColumnType($tableName, $columnName, $columnType)
    {
        $this->setOptions(
            sprintf(static::COLUMN_OPTION_FORMAT, $tableName, $columnName),
            [self::TYPE_OPTION => $columnType]
        );
    }

    /**
     * Removes column options from the options manager
     *
     * @param string $tableName
     * @param string $columnName
     */
    public function removeColumnOptions($tableName, $columnName)
    {
        $objectKey = sprintf(static::COLUMN_OPTION_FORMAT, $tableName, $columnName);
        unset($this->options[$objectKey], $this->options[self::APPEND_SECTION][$objectKey]);
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
        $this->handleAppendSection($objectKey, $options);
        foreach ($options as $scope => $values) {
            $this->validateOption($objectKey, $scope, $values);
            if (isset($this->options[$objectKey][$scope]) && is_array($values)) {
                foreach ($values as $attrName => $val) {
                    if ($this->isAppend($objectKey, $scope, $attrName)
                        && isset($this->options[$objectKey][$scope][$attrName])
                    ) {
                        $this->options[$objectKey][$scope][$attrName] = array_merge(
                            (array)$this->options[$objectKey][$scope][$attrName],
                            (array)$val
                        );
                    } else {
                        $this->options[$objectKey][$scope][$attrName] = $val;
                    }
                }
            } else {
                $this->options[$objectKey][$scope] = $values;
            }
        }
    }

    /**
     * Gets a value of an option with the given name and then remove the option from $options array
     *
     * @param array  $options
     * @param string $name
     * @return mixed
     */
    protected function getAndRemoveOption(array &$options, $name)
    {
        $value = null;
        if (isset($options[$name])) {
            $value = $options[$name];
            unset($options[$name]);
        }

        return $value;
    }

    /**
     * @param string $objectKey
     * @param array  $options
     */
    protected function handleAppendSection($objectKey, array &$options)
    {
        $appendSection = $this->getAndRemoveOption($options, OroOptions::APPEND_SECTION);
        if (!empty($appendSection)) {
            foreach ($appendSection as $scope => $attrNames) {
                foreach ($attrNames as $attrName) {
                    if (!isset($this->options[self::APPEND_SECTION])) {
                        $this->options[self::APPEND_SECTION] = [];
                    }
                    if (!isset($this->options[self::APPEND_SECTION][$objectKey])) {
                        $this->options[self::APPEND_SECTION][$objectKey] = [];
                    }
                    if (!isset($this->options[self::APPEND_SECTION][$objectKey][$scope])) {
                        $this->options[self::APPEND_SECTION][$objectKey][$scope] = [];
                    }
                    if (!in_array($attrName, $this->options[self::APPEND_SECTION][$objectKey][$scope], true)) {
                        $this->options[self::APPEND_SECTION][$objectKey][$scope][] = $attrName;
                    }
                }
            }
        }
    }

    /**
     * @param string $objectKey
     * @param string $scope
     * @param string $attrName
     * @return bool
     */
    protected function isAppend($objectKey, $scope, $attrName)
    {
        return
            isset($this->options[self::APPEND_SECTION][$objectKey][$scope]) &&
            in_array($attrName, $this->options[self::APPEND_SECTION][$objectKey][$scope], true);
    }

    /**
     * @param $objectKey
     * @param $scope
     * @param $values
     * @throws \InvalidArgumentException
     */
    private function validateOption($objectKey, $scope, $values)
    {
        if (!is_string($scope) || empty($scope)) {
            throw new \InvalidArgumentException(
                sprintf('A scope name must be non empty string. Key: %s.', $objectKey)
            );
        }

        // a scope which name starts with '_' is a temporary and it should be removed in ExtendOptionsBuilder
        if (!is_array($values) && strpos($scope, '_') !== 0) {
            throw new \InvalidArgumentException(
                sprintf('A value of "%s" scope must be an array. Key: %s.', $scope, $objectKey)
            );
        }
    }
}
