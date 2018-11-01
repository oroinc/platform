<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\FilterValue;

class FilterValueTest extends \PHPUnit\Framework\TestCase
{
    public function testGetSetPath()
    {
        $filterValue = new FilterValue('path', 'value', 'operator');

        self::assertSame('path', $filterValue->getPath());
        $filterValue->setPath('path2');
        self::assertSame('path2', $filterValue->getPath());
    }

    public function testGetSetValue()
    {
        $filterValue = new FilterValue('path', 'value', 'operator');

        self::assertSame('value', $filterValue->getValue());

        $filterValue->setValue('value2');
        self::assertSame('value2', $filterValue->getValue());

        $filterValue->setValue(['value1', 'value2']);
        self::assertSame(['value1', 'value2'], $filterValue->getValue());
    }

    public function testGetSetOperator()
    {
        $filterValue = new FilterValue('path', 'value', 'operator');

        self::assertSame('operator', $filterValue->getOperator());
        $filterValue->setOperator('operator2');
        self::assertSame('operator2', $filterValue->getOperator());
    }

    public function testDefaultOperator()
    {
        $filterValue = new FilterValue('path', 'value');

        self::assertNull($filterValue->getOperator());
    }

    public function testGetSetSourceKey()
    {
        $filterValue = new FilterValue('path', 'value', 'operator');

        self::assertNull($filterValue->getSourceKey());
        $filterValue->setSourceKey('key');
        self::assertSame('key', $filterValue->getSourceKey());
    }
}
