<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\MetaPropertyFilter;

class MetaPropertyFilterTest extends \PHPUnit\Framework\TestCase
{
    public function testAllowedMetaProperties()
    {
        $filter = new MetaPropertyFilter('string');

        self::assertSame([], $filter->getAllowedMetaProperties());

        $filter->addAllowedMetaProperty('test1', 'string');
        $filter->addAllowedMetaProperty('test2', null);
        self::assertSame(['test1' => 'string', 'test2' => null], $filter->getAllowedMetaProperties());

        $filter->addAllowedMetaProperty('test1', 'integer');
        $filter->addAllowedMetaProperty('test2', 'string');
        self::assertSame(['test1' => 'integer', 'test2' => 'string'], $filter->getAllowedMetaProperties());

        $filter->removeAllowedMetaProperty('test1');
        self::assertSame(['test2' => 'string'], $filter->getAllowedMetaProperties());
    }
}
