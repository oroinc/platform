<?php

namespace Oro\Bundle\BatchBundle\ORM\Query\ResultIterator;

use Doctrine\ORM\Internal\Hydration\AbstractHydrator;

/**
 * Hydrates first result column as integer numbers. Used to fetch IDs of a Query
 */
class IdentifierHydrator extends AbstractHydrator
{
    /**
     * {@inheritdoc}
     */
    protected function hydrateAllData()
    {
        $result = [];
        while ($data = $this->_stmt->fetchOne()) {
            $result[] = $data;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function hydrateRow()
    {
        $column = $this->_stmt->fetchOne();

        if ($column === false || $column === null) {
            $this->cleanup();

            return false;
        }

        return [$column];
    }
}
