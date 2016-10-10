<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Formatter\Property;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\CallbackProperty;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyConfiguration;

class CallbackPropertyTest extends \PHPUnit_Framework_TestCase
{
    /** @var \stdClass|\PHPUnit_Framework_MockObject_MockObject */
    protected $callable;

    /** @var CallbackProperty */
    protected $property;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->callable = $this->getMockBuilder('stdClass')
            ->setMethods(['virtualMethod'])
            ->getMock();

        $this->property = new CallbackProperty();
    }

    public function testGetRawValue()
    {
        $record = new ResultRecord([]);

        $this->callable->expects($this->once())
            ->method('virtualMethod')
            ->with($record)
            ->willReturn('returnValue');

        $this->property->init(PropertyConfiguration::create([
            CallbackProperty::CALLABLE_KEY => [$this->callable, 'virtualMethod'],
        ]));

        $this->assertSame('returnValue', $this->property->getRawValue($record));
    }

    public function testGetRawValueAndParams()
    {
        $record = new ResultRecord(['param1' => 'value1', 'param2' => 'value2']);

        $this->callable->expects($this->once())
            ->method('virtualMethod')
            ->with('value1', 'value2')
            ->willReturn('returnValue');

        $this->property->init(PropertyConfiguration::create([
            CallbackProperty::CALLABLE_KEY => [$this->callable, 'virtualMethod'],
            CallbackProperty::PARAMS_KEY => ['param1', 'param2'],
        ]));

        $this->assertSame('returnValue', $this->property->getRawValue($record));
    }
}
