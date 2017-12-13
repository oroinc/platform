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
        while ($data = $this->_stmt->fetch(\PDO::FETCH_COLUMN)) {
            $result[] = $data;
        }

        return $result;
    }
}
