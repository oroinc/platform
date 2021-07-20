<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\ORM\QueryBuilder;

/**
 * Abstract implementation of bulk insert query executor.
 *
 * @deprecated Implement InsertQueryExecutorInterface, current Abstract implementation is empty and will be removed.
 */
abstract class AbstractInsertQueryExecutor implements InsertQueryExecutorInterface
{
    /**
     * @deprecated
     * @var array
     */
    protected $tablesColumns;

    /**
     * @var NativeQueryExecutorHelper
     */
    protected $helper;

    public function __construct(NativeQueryExecutorHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * {@inheritDoc}
     */
    abstract public function execute(string $className, array $fields, QueryBuilder $selectQueryBuilder): int;

    /**
     * @deprecated use NativeQueryExecutorHelper::getColumns instead
     * @param string $className
     * @param array $fields
     * @return array
     */
    protected function getColumns($className, array $fields)
    {
        return $this->helper->getColumns($className, $fields);
    }
}
