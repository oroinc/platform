<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\FilterValue;

class FilterValueTest extends \PHPUnit_Framework_TestCase
{
    /** @var FilterValue */
    protected $filter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->filter = new FilterValue('path', 'value', 'operator');
    }

    public function testGetSetPath()
    {
        $this->assertSame('path', $this->filter->getPath());
        $this->filter->setPath('path2');
        $this->assertSame('path2', $this->filter->getPath());
    }

    public function testGetSetValue()
    {
        $this->assertSame('value', $this->filter->getValue());

        $this->filter->setValue('value2');
        $this->assertSame('value2', $this->filter->getValue());

        $this->filter->setValue(['value1', 'value2']);
        $this->assertSame(['value1', 'value2'], $this->filter->getValue());
    }

    public function testGetSetOperator()
    {
        $this->assertSame('operator', $this->filter->getOperator());
        $this->filter->setOperator('operator2');
        $this->assertSame('operator2', $this->filter->getOperator());
    }
}
