<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Datasource\Orm;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;

class OrmFilterDatasourceAdapterTest extends \PHPUnit\Framework\TestCase
{
    public function testGenerateParameterName()
    {
        $parameters = new ArrayCollection([new Parameter('_gpnpint1', 1)]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->any())
            ->method('getParameters')
            ->willReturn($parameters);

        $adapter1 = new OrmFilterDatasourceAdapter($qb);
        $adapter2 = new OrmFilterDatasourceAdapter($qb);

        // Check that counters for the same prefix are increased
        $this->assertEquals('_gpnpstring1', $adapter1->generateParameterName('string'));
        $this->assertEquals('_gpnpstring2', $adapter1->generateParameterName('string'));
        // Check that counters are prefix related
        $this->assertEquals('_gpnpdate1', $adapter1->generateParameterName('date'));
        // Check that if parameter is present in QB its name will be not generated
        $this->assertEquals('_gpnpint2', $adapter1->generateParameterName('int'));

        // Check that second adapter use its own counters.
        $this->assertEquals('_gpnpstring1', $adapter2->generateParameterName('string'));
    }
}
