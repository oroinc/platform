<?php

namespace Oro\Bundle\EntityBundle\ORM\Repository;

use Doctrine\ORM\Internal\Hydration\IterableResult;

interface BatchIteratorInterface
{
    /**
     * @return IterableResult
     */
    public function getBatchIterator();
}
