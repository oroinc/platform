<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Connector\Search;

use Oro\Bundle\ImapBundle\Connector\Search\SearchQueryExprOperator;
use PHPUnit\Framework\TestCase;

class SearchQueryExprOperatorTest extends TestCase
{
    public function testConstructor(): void
    {
        $name = 'testName';
        $obj = new SearchQueryExprOperator($name);

        $this->assertEquals($name, $obj->getName());
    }

    public function testSettersAndGetters(): void
    {
        $obj = new SearchQueryExprOperator('1');

        $name = 'testName';

        $obj->setName($name);

        $this->assertEquals($name, $obj->getName());
    }
}
