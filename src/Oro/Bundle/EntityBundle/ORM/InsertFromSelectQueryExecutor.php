<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\ORM\QueryBuilder;

class InsertFromSelectQueryExecutor
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
     * @param string $className
     * @param array $fields
     * @param QueryBuilder $selectQueryBuilder
     * @return int
     */
    public function execute($className, array $fields, QueryBuilder $selectQueryBuilder)
    {
        $insertToTableName = $this->helper->getTableName($className);
        $columns = $this->getColumns($className, $fields);
        $selectQuery = $selectQueryBuilder->getQuery();

        $sql = sprintf('insert into %s (%s) %s', $insertToTableName, implode(', ', $columns), $selectQuery->getSQL());
        list($params, $types) = $this->helper->processParameterMappings($selectQuery);
        // No possibility to use createNativeQuery with rsm http://www.doctrine-project.org/jira/browse/DDC-962
        return $this->helper->getManager($className)->getConnection()->executeUpdate($sql, $params, $types);
    }

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
