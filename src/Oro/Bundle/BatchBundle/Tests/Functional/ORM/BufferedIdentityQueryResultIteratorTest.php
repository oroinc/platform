<?php

namespace Oro\Bundle\BatchBundle\Tests\Functional\ORM;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\AbstractBufferedQueryResultIterator;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;

class BufferedIdentityQueryResultIteratorTest extends AbstractBufferedIdentityQueryResultIteratorTest
{
    #[\Override]
    public function getIterator(QueryBuilder $queryBuilder): AbstractBufferedQueryResultIterator
    {
        return new BufferedIdentityQueryResultIterator($queryBuilder);
    }
}
