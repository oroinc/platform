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

    public function testProcessed()
    {
        $this->assertFalse($this->context->isProcessed());
        $this->context->setProcessed(true);
        $this->assertTrue($this->context->isProcessed());
    }

    public function testArrayDelimiter()
    {
        $this->assertEquals(',', $this->context->getArrayDelimiter());
        $this->context->setArrayDelimiter('-');
        $this->assertEquals('-', $this->context->getArrayDelimiter());
    }

    public function testRangeDelimiter()
    {
        $this->assertEquals('..', $this->context->getRangeDelimiter());
        $this->context->setRangeDelimiter('|');
        $this->assertEquals('|', $this->context->getRangeDelimiter());
    }

    public function testDataType()
    {
        $this->assertFalse($this->context->has('dataType'));
        $this->context->setDataType('string');
        $this->assertEquals('string', $this->context->getDataType());
        $this->assertEquals('string', $this->context->get('dataType'));
    }

    public function testRequirement()
    {
        $this->assertFalse($this->context->has('requirement'));
        $this->context->setRequirement('.+');
        $this->assertEquals('.+', $this->context->getRequirement());
        $this->assertEquals('.+', $this->context->get('requirement'));
        $this->context->removeRequirement();
        $this->assertFalse($this->context->has('requirement'));
    }

    public function testArrayAllowed()
    {
        $this->assertFalse($this->context->has('arrayAllowed'));
        $this->context->setArrayAllowed(true);
        $this->assertTrue($this->context->isArrayAllowed());
        $this->assertTrue($this->context->get('arrayAllowed'));
    }

    public function testRangeAllowed()
    {
        $this->assertFalse($this->context->has('rangeAllowed'));
        $this->context->setRangeAllowed(true);
        $this->assertTrue($this->context->isRangeAllowed());
        $this->assertTrue($this->context->get('rangeAllowed'));
    }
}
