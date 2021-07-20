<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\ORM\QueryBuilder;

/**
 * Compiles and executes "insert from select" query
 */
class InsertFromSelectQueryExecutor implements InsertQueryExecutorInterface
{
    /**
     * @var NativeQueryExecutorHelper
     */
    private $helper;

    public function __construct(NativeQueryExecutorHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * {@inheritDoc}
     */
    public function execute(string $className, array $fields, QueryBuilder $selectQueryBuilder):int
    {
        $insertToTableName = $this->helper->getTableName($className);
        $columns = $this->helper->getColumns($className, $fields);
        $selectQuery = $selectQueryBuilder->getQuery();

        $sql = sprintf('insert into %s (%s) %s', $insertToTableName, implode(', ', $columns), $selectQuery->getSQL());
        list($params, $types) = $this->helper->processParameterMappings($selectQuery);
        // No possibility to use createNativeQuery with rsm http://www.doctrine-project.org/jira/browse/DDC-962
        return $this->helper->getManager($className)->getConnection()->executeStatement($sql, $params, $types);
    }
}
