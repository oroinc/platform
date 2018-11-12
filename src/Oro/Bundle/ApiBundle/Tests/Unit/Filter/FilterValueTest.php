<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\FilterValue;

class FilterValueTest extends \PHPUnit\Framework\TestCase
{
    public function testPath()
    {
        $filterValue = new FilterValue('path', 'value', 'operator');

        self::assertSame('path', $filterValue->getPath());
        $filterValue->setPath('path2');
        self::assertSame('path2', $filterValue->getPath());
    }

    public function testValue()
    {
        $filterValue = new FilterValue('path', 'value', 'operator');

        self::assertSame('value', $filterValue->getValue());

        $filterValue->setValue('value2');
        self::assertSame('value2', $filterValue->getValue());

        $filterValue->setValue(['value1', 'value2']);
        self::assertSame(['value1', 'value2'], $filterValue->getValue());
    }

    public function testOperator()
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

    public function testFilterValueCreatedWithSourceKeyAndValue()
    {
        $filterValue = FilterValue::createFromSource('src_key', 'path', 'value', 'operator');

        self::assertSame('src_key', $filterValue->getSourceKey());
        self::assertSame('value', $filterValue->getSourceValue());
        self::assertSame('path', $filterValue->getPath());
        self::assertSame('value', $filterValue->getValue());
        self::assertSame('operator', $filterValue->getOperator());
    }

    public function testFilterValueCreatedWithoutSourceKeyAndValue()
    {
        $filterValue = new FilterValue('path', 'value', 'operator');

        self::assertNull($filterValue->getSourceKey());
        self::assertNull($filterValue->getSourceValue());
        self::assertSame('path', $filterValue->getPath());
        self::assertSame('value', $filterValue->getValue());
        self::assertSame('operator', $filterValue->getOperator());
    }
}
