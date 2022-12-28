<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Functional\ORM\Query;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\AbstractBufferedQueryResultIterator;
use Oro\Bundle\BatchBundle\Tests\Functional\ORM\AbstractBufferedIdentityQueryResultIteratorTest;
use Oro\Bundle\ImportExportBundle\ORM\Query\ExportBufferedIdentityQueryResultIterator;

class ExportBufferedIdentityQueryResultIteratorTest extends AbstractBufferedIdentityQueryResultIteratorTest
{
    public function getIterator(QueryBuilder $queryBuilder): AbstractBufferedQueryResultIterator
    {
        return new ExportBufferedIdentityQueryResultIterator($queryBuilder);
    }
}
