<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\NormalizeValue;

use Oro\Bundle\ApiBundle\Processor\NormalizeValue\NormalizeValueContext;

class NormalizeValueContextTest extends \PHPUnit_Framework_TestCase
{
    /** @var NormalizeValueContext */
    protected $context;

    public function setUp()
    {
        $this->context = new NormalizeValueContext();
    }

    public function testArrayDelimiter()
    {
        $this->assertEquals(',', $this->context->getArrayDelimiter());
        $this->context->setArrayDelimiter('-');
        $this->assertEquals('-', $this->context->getArrayDelimiter());
    }

    public function testDataType()
    {
        $this->assertFalse($this->context->has('dataType'));
        $this->context->setDataType('string');
        $this->assertEquals('string', $this->context->getDataType());
    }

    public function testRequirement()
    {
        $this->assertFalse($this->context->has('requirement'));
        $this->context->setRequirement('.+');
        $this->assertEquals('.+', $this->context->getRequirement());
        $this->context->removeRequirement();
        $this->assertFalse($this->context->has('requirement'));
    }

    public function testArrayAllowed()
    {
        $this->assertFalse($this->context->has('dataType'));
        $this->context->setArrayAllowed(true);
        $this->assertTrue($this->context->isArrayAllowed());
    }
}
