<?php

namespace Oro\Bundle\EntityExtendBundle\Extend\Schema;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

class ExtendOptionManager
{
    const NAME_DELIMITER = '.';

    /**
     * @var EntityClassResolver
     */
    protected $entityClassResolver;

    /**
     * @var array key = [table name] or [table name!column name!column type]
     */
    protected $options = [];

    public function __construct(EntityClassResolver $entityClassResolver)
    {
        $this->entityClassResolver = $entityClassResolver;
    }

    public function getEntityClassResolver()
    {
        return $this->entityClassResolver;
    }

    public function addTableOptions($tableName, $options)
    {
        $this->setOptions($tableName, $options);
    }

    public function addTableOption($tableName, $name, $value)
    {
        $this->setOption($tableName, $name, $value);
    }

    public function addColumnOptions($tableName, $columnName, $columnType, $options)
    {
        $this->setOptions(sprintf('%s!%s!%s', $tableName, $columnName, $columnType), $options);
    }

    public function addColumnOption($tableName, $columnName, $columnType, $name, $value)
    {
        $this->setOption(sprintf('%s!%s!%s', $tableName, $columnName, $columnType), $name, $value);
    }

    public function getExtendOptions()
    {
        $builder = new ExtendOptionBuilder($this->entityClassResolver);

        $keys = array_keys($this->options);
        sort($keys);
        foreach ($keys as $objectKey) {
            $keyParts   = explode('!', $objectKey);
            $tableName  = $keyParts[0];
            $columnName = count($keyParts) > 1 ? $keyParts[1] : null;
            $columnType = count($keyParts) > 1 ? $keyParts[2] : null;

            if ($columnName) {
                $builder->addColumnOptions($tableName, $columnName, $columnType, $this->options[$objectKey]);
            }
        }

        return $builder->get();
    }

    protected function setOptions($objectKey, $options)
    {
        if (!empty($options)) {
            if (!is_array($options)) {
                throw new \InvalidArgumentException('Options must be an array.');
            }
            if (!isset($this->options[$objectKey])) {
                $this->options[$objectKey] = [];
            }
            foreach ($options as $scope => $values) {
                if (!is_string($scope) || empty($scope)) {
                    throw new \InvalidArgumentException('A scope name must be non empty string.');
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

    protected function setOption($objectKey, $name, $value)
    {
        if (!isset($this->options[$objectKey])) {
            $this->options[$objectKey] = [];
        }
        $options       = & $this->options[$objectKey];
        $nameParts     = explode(self::NAME_DELIMITER, $name);
        $namePartCount = count($nameParts);
        for ($i = 0; $i < $namePartCount; $i++) {
            $namePart = $nameParts[$i];
            if (empty($namePart)) {
                throw new \InvalidArgumentException(sprintf('Invalid option name: %s.', $name));
            }
            if ($i === $namePartCount - 1) {
                $options[$namePart] = $value;
            } else {
                if (!isset($options[$namePart])) {
                    $options[$namePart] = [];
                }
                $options = & $options[$namePart];
            }
        }
    }
}
