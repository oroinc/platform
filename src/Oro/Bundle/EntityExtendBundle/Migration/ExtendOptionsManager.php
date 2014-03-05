<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

class ExtendOptionsManager
{
    /**
     * @var EntityClassResolver
     */
    protected $entityClassResolver;

    /**
     * @var array key = [table name] or [table name!column name!column type]
     */
    protected $options = [];

    /**
     * @param EntityClassResolver $entityClassResolver
     */
    public function __construct(EntityClassResolver $entityClassResolver)
    {
        $this->entityClassResolver = $entityClassResolver;
    }

    /**
     * @return EntityClassResolver
     */
    public function getEntityClassResolver()
    {
        return $this->entityClassResolver;
    }

    /**
     * Sets table options
     *
     * @param string $tableName
     * @param array $options
     */
    public function setTableOptions($tableName, array $options)
    {
        $this->setOptions($tableName, $options);
    }

    /**
     * Sets column options
     *
     * @param string $tableName
     * @param string $columnName
     * @param string $columnType
     * @param array $options
     */
    public function setColumnOptions($tableName, $columnName, $columnType, array $options)
    {
        $this->setOptions(sprintf('%s!%s!%s', $tableName, $columnName, $columnType), $options);
    }

    /**
     * Gets all options
     *
     * @return ExtendOptionsProviderInterface
     */
    public function getExtendOptionsProvider()
    {
        $builder = new ExtendOptionsBuilder($this->entityClassResolver);

        $objectKeys = array_keys($this->options);

        // at first all table's options should be processed,
        // because it is possible that a reference to new table is created
        foreach ($objectKeys as $objectKey) {
            if (!strpos($objectKey, '!')) {
                $builder->addTableOptions($objectKey, $this->options[$objectKey]);
            }
        }

        // next column's options for all tables can be processed
        foreach ($objectKeys as $objectKey) {
            if (strpos($objectKey, '!')) {
                $keyParts = explode('!', $objectKey);
                $builder->addColumnOptions($keyParts[0], $keyParts[1], $keyParts[2], $this->options[$objectKey]);
            }
        }

        return $builder;
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
            if (isset($this->options[$objectKey][$scope])) {
                if (!is_array($values)) {
                    throw new \InvalidArgumentException(
                        sprintf('A value of "%s" scope must be an array. Key: %s.', $scope, $objectKey)
                    );
                }
                foreach ($values as $key => $val) {
                    $this->options[$objectKey][$scope][$key] = $val;
                }
            } else {
                $this->options[$objectKey][$scope] = $values;
            }
        }
    }
}
