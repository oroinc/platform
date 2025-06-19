<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Connector\Search;

use Oro\Bundle\ImapBundle\Connector\Search\SearchQueryExprItem;
use PHPUnit\Framework\TestCase;

class SearchQueryExprItemTest extends TestCase
{
    public function testConstructor(): void
    {
        $name = 'testName';
        $value = 'testValue';
        $match = 1;
        $obj = new SearchQueryExprItem($name, $value, $match);

        $this->assertEquals($name, $obj->getName());
        $this->assertEquals($value, $obj->getValue());
        $this->assertEquals($match, $obj->getMatch());
    }

    public function testSettersAndGetters(): void
    {
        $obj = new SearchQueryExprItem('1', '1', '=', 0, false);

        $name = 'testName';
        $value = 'testValue';
        $match = 1;

        $obj->setName($name);
        $obj->setValue($value);
        $obj->setMatch($match);

        $this->assertEquals($name, $obj->getName());
        $this->assertEquals($value, $obj->getValue());
        $this->assertEquals($match, $obj->getMatch());
    }
}
