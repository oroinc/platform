<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Connector\Search;

use Oro\Bundle\ImapBundle\Connector\Search\SearchQueryExprValue;
use PHPUnit\Framework\TestCase;

class SearchQueryExprValueTest extends TestCase
{
    public function testConstructor(): void
    {
        $value = 'testValue';
        $match = 1;
        $obj = new SearchQueryExprValue($value, $match);

        $this->assertEquals($value, $obj->getValue());
        $this->assertEquals($match, $obj->getMatch());
    }

    public function testSettersAndGetters(): void
    {
        $obj = new SearchQueryExprValue('1', 0);

        $value = 'testValue';
        $match = 1;

        $obj->setValue($value);
        $obj->setMatch($match);

        $this->assertEquals($value, $obj->getValue());
        $this->assertEquals($match, $obj->getMatch());
    }
}
