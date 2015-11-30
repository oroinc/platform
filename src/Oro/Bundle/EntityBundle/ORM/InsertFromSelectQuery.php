<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\ORM\QueryBuilder;

class InsertFromSelectQuery extends AbstractNativeQuery
{
    /**
     * @var array
     */
    protected $tablesColumns;

    /**
     * @param string $className
     * @param array $fields
     * @param QueryBuilder $selectQueryBuilder
     */
    public function execute($className, array $fields, QueryBuilder $selectQueryBuilder)
    {
        $insertToTableName = $this->getTableName($className);

        $columns = $this->getColumns($className, $fields);

        $selectQuery = $selectQueryBuilder->getQuery();

        $sql = sprintf('insert into %s (%s) %s', $insertToTableName, implode(', ', $columns), $selectQuery->getSQL());

        list($params, $types) = $this->processParameterMappings($selectQuery);

        // No possibility to use createNativeQuery with rsm http://www.doctrine-project.org/jira/browse/DDC-962
        $this->getManager($className)->getConnection()->executeUpdate($sql, $params, $types);
    }

    /**
     * @param string $className
     * @param array $fields
     * @return string
     */
    protected function getColumns($className, array $fields)
    {
        $result = [];

        foreach ($fields as $field) {
            if (!isset($this->tablesColumns[$className]) || !isset($this->tablesColumns[$className][$field])) {
                if ($this->getClassMetadata($className)->hasAssociation($field)) {
                    $mapping = $this->getClassMetadata($className)->getAssociationMapping($field);
                    $this->tablesColumns[$className][$field] = array_shift($mapping['joinColumnFieldNames']);
                } else {
                    $this->tablesColumns[$className][$field] = $this->getClassMetadata($className)
                        ->getColumnName($field);
                }
            }
            $result[] = $this->tablesColumns[$className][$field];
        }

        return $result;
    }
}
