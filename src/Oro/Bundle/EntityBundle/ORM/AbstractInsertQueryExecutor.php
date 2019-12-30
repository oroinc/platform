<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\ORM\QueryBuilder;

/**
 * Abstract implementation of bulk insert query executor
 */
abstract class AbstractInsertQueryExecutor implements InsertQueryExecutorInterface
{
    /**
     * @var array
     */
    protected $tablesColumns;

    /**
     * @var NativeQueryExecutorHelper
     */
    protected $helper;

    /**
     * @param NativeQueryExecutorHelper $helper
     */
    public function __construct(NativeQueryExecutorHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * {@inheritDoc}
     */
    abstract public function execute(string $className, array $fields, QueryBuilder $selectQueryBuilder): int;

    /**
     * @param string $className
     * @param array $fields
     * @return array
     */
    protected function getColumns($className, array $fields)
    {
        $result = [];

        foreach ($fields as $field) {
            if (!isset($this->tablesColumns[$className][$field])) {
                $classMetadata = $this->helper->getClassMetadata($className);
                if (!$classMetadata->hasField($field) && !$classMetadata->hasAssociation($field)) {
                    throw new \InvalidArgumentException(sprintf('Field %s is not known for %s', $field, $className));
                }
                if ($classMetadata->hasAssociation($field)) {
                    $mapping = $classMetadata->getAssociationMapping($field);
                    $this->tablesColumns[$className][$field] = array_shift($mapping['joinColumnFieldNames']);
                } else {
                    $this->tablesColumns[$className][$field] = $classMetadata->getColumnName($field);
                }
            }
            $result[] = $this->tablesColumns[$className][$field];
        }

        return $result;
    }
}
