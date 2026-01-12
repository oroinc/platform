<?php

namespace Oro\Bundle\ImportExportBundle\Writer;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

/**
 * Writes data using `INSERT FROM SELECT` SQL queries.
 *
 * This writer uses native SQL `INSERT FROM SELECT` queries to efficiently bulk-insert
 * data into the database. It supports optional cleanup of outdated records before
 * insertion and allows configuration of which fields to insert. This approach is
 * more efficient than inserting records one by one, especially for large datasets.
 */
class InsertFromSelectWriter extends AbstractNativeQueryWriter
{
    /**
     * @var array
     */
    protected $fields;

    /**
     * @var InsertFromSelectQueryExecutor
     */
    protected $insertFromSelectQueryExecutor;

    public function __construct(InsertFromSelectQueryExecutor $insertFromSelectQuery)
    {
        $this->insertFromSelectQueryExecutor = $insertFromSelectQuery;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    #[\Override]
    public function write(array $items)
    {
        foreach ($items as $item) {
            if ($this instanceof CleanUpInterface) {
                $this->cleanUp($item);
            }

            $this->insertFromSelectQueryExecutor->execute(
                $this->entityName,
                $this->getFields(),
                $this->getQueryBuilder($item)
            );
        }
    }

    /**
     * @param array $fields
     * @return $this
     */
    public function setFields(array $fields)
    {
        $this->fields = $fields;

        return $this;
    }
}
