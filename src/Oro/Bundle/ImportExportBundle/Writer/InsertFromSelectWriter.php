<?php

namespace Oro\Bundle\ImportExportBundle\Writer;

use Doctrine\ORM\Query;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQuery;

class InsertFromSelectWriter extends AbstractNativeQueryWriter
{
    /**
     * @var array
     */
    protected $fields;

    /**
     * @var InsertFromSelectQuery
     */
    protected $insertFromSelectQuery;

    /**
     * @param InsertFromSelectQuery $insertFromSelectQuery
     */
    public function __construct(InsertFromSelectQuery $insertFromSelectQuery)
    {
        $this->insertFromSelectQuery = $insertFromSelectQuery;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        foreach ($items as $item) {
            if ($this instanceof CleanUpInterface) {
                $this->cleanUp($item);
            }

            $this->insertFromSelectQuery->execute($this->getEntityName(), $this->fields, $this->getQueryBuilder($item));
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
