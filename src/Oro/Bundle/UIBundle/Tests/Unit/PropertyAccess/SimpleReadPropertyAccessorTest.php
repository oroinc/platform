<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\PropertyAccess;

use Oro\Bundle\UIBundle\PropertyAccess\SimpleReadPropertyAccessor;
use Oro\Bundle\UIBundle\Tests\Unit\Fixture\TestClassForPropertyAccessor;

class SimpleReadPropertyAccessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var SimpleReadPropertyAccessor */
    protected $accessor;

    /** @var TestClassForPropertyAccessor */
    protected $testObj;

    protected function setUp()
    {
        $this->accessor = new SimpleReadPropertyAccessor(true);

        $this->testObj = new TestClassForPropertyAccessor();
        $this->testObj->setPrivateProp('PrivateProp');
        $this->testObj->setProtectedProp('ProtectedProp');
        $this->testObj->publicProp = 'PublicProp';
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The "setValue" method is not implemented by this class.
     */
    public function testNotSupportedSetValue()
    {
        $this->accessor->setValue(new \stdClass(), 'test', 123);
    }

    /**
     * @dataProvider getValueProvider
     */
    public function testGetValue($propertyName, $expectedValue)
    {
        $result = $this->accessor->getValue($this->testObj, $propertyName);
        $this->assertEquals($expectedValue, $result);
    }

    public function getValueProvider()
    {
        return [
            ['privateProp', 'PrivateProp'],
            ['protectedProp', 'ProtectedProp'],
            ['publicProp', 'PublicProp'],
        ];
    }
}
